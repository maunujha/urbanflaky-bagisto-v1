<?php

namespace Webkul\Shop\Http\Controllers\Customer;

use Carbon\Carbon;
use Cookie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Webkul\Core\Repositories\SubscribersListRepository;
use Webkul\Customer\Repositories\CustomerGroupRepository;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Shop\Http\Controllers\Controller;
use Webkul\Shop\Http\Requests\Customer\RegistrationRequest;
use Webkul\Shop\Mail\Customer\EmailVerificationNotification;
use Webkul\Shop\Mail\Customer\RegistrationNotification;
use Webkul\Shop\Services\SmsAlertService;

class RegistrationController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected CustomerRepository $customerRepository,
        protected CustomerGroupRepository $customerGroupRepository,
        protected SubscribersListRepository $subscriptionRepository,
        protected SmsAlertService $smsAlertService
    ) {}

    /**
     * Opens up the user's sign up form.
     * Passing ?reset=1 clears any pending OTP session (used by "Change number" link).
     */
    public function index(Request $request): View|RedirectResponse
    {
        if (auth()->guard('customer')->check()) {
            return redirect()->route('shop.home.index');
        }

        if ($request->query('reset')) {
            session()->forget(['signup_pending', 'signup_otp', 'signup_otp_expires_at', 'show_otp_modal']);
        }

        return view('shop::customers.sign-up');
    }

    /**
     * Validate signup form, send OTP, and hold account creation until phone is verified.
     *
     * @return Response
     */
    public function store(RegistrationRequest $registrationRequest): RedirectResponse
    {
        $customerGroup = core()->getConfigData('customer.settings.create_new_account_options.default_group');
        $phone         = $registrationRequest->input('phone');
        $autoEmail     = $phone . '@noreply.' . parse_url(config('app.url'), PHP_URL_HOST);

        session()->put('signup_pending', [
            'first_name'               => $registrationRequest->input('first_name'),
            'last_name'                => $registrationRequest->input('last_name'),
            'email'                    => $autoEmail,
            'phone'                    => $phone,
            'password'                 => bcrypt(Str::random(32)),
            'api_token'                => Str::random(80),
            'is_verified'              => 1,
            'customer_group_id'        => $this->customerGroupRepository->findOneWhere(['code' => $customerGroup])->id,
            'channel_id'               => core()->getCurrentChannel()->id,
            'token'                    => null,
            'subscribed_to_news_letter' => false,
            'had_prior_subscription'   => false,
            'prior_subscription_id'    => null,
        ]);

        $otp = (string) random_int(1000, 9999);

        session()->put('signup_otp', $otp);
        session()->put('signup_otp_expires_at', Carbon::now()->addMinutes((int) config('services.smsalert.otp_expiry', 10))->toDateTimeString());

        $sent = $this->smsAlertService->sendOtp($registrationRequest->input('phone'), $otp);

        if (! $sent) {
            session()->forget(['signup_pending', 'signup_otp', 'signup_otp_expires_at']);
            session()->flash('error', trans('shop::app.customers.otp.send-failed'));

            return redirect()->back()->withInput();
        }

        session()->flash('show_otp_modal', true);

        return redirect()->route('shop.customers.register.index');
    }

    /**
     * Show the phone OTP verification page for signup.
     */
    public function showPhoneVerify(): RedirectResponse|View
    {
        if (! session()->has('signup_pending')) {
            return redirect()->route('shop.customers.register.index');
        }

        return view('shop::customers.signup-otp-verify');
    }

    /**
     * Verify the phone OTP and create the customer account.
     */
    public function verifyPhone(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => 'required|digits:4',
        ], [
            'otp.required' => trans('shop::app.customers.otp.otp-required'),
            'otp.digits'   => trans('shop::app.customers.otp.otp-digits'),
        ]);

        if (! session()->has('signup_pending')) {
            return redirect()->route('shop.customers.register.index');
        }

        if ($request->otp !== session('signup_otp')) {
            session()->flash('error', trans('shop::app.customers.otp.invalid-otp'));
            session()->flash('show_otp_modal', true);

            return redirect()->route('shop.customers.register.index');
        }

        if (Carbon::now()->isAfter(session('signup_otp_expires_at'))) {
            session()->flash('error', trans('shop::app.customers.otp.otp-expired'));
            session()->flash('show_otp_modal', true);

            return redirect()->route('shop.customers.register.index');
        }

        $data = session()->pull('signup_pending');
        session()->forget(['signup_otp', 'signup_otp_expires_at']);

        Event::dispatch('customer.registration.before');

        $customer = $this->customerRepository->create([
            'first_name'               => $data['first_name'],
            'last_name'                => $data['last_name'],
            'email'                    => $data['email'],
            'phone'                    => $data['phone'],
            'password'                 => $data['password'],
            'api_token'                => $data['api_token'],
            'is_verified'              => 1,
            'customer_group_id'        => $data['customer_group_id'],
            'channel_id'               => $data['channel_id'],
            'token'                    => null,
            'subscribed_to_news_letter' => $data['subscribed_to_news_letter'],
        ]);

        $subscription = $data['had_prior_subscription']
            ? $this->subscriptionRepository->find($data['prior_subscription_id'])
            : null;

        if ($subscription) {
            $this->subscriptionRepository->update(['customer_id' => $customer->id], $subscription->id);
        } elseif (! empty($data['is_subscribed'])) {
            Event::dispatch('customer.subscription.before');

            $newSubscription = $this->subscriptionRepository->create([
                'email'         => $data['email'],
                'customer_id'   => $customer->id,
                'channel_id'    => $data['channel_id'],
                'is_subscribed' => 1,
                'token'         => uniqid(),
            ]);

            Event::dispatch('customer.subscription.after', $newSubscription);
        }

        Event::dispatch('customer.create.after', $customer);
        Event::dispatch('customer.registration.after', $customer);

        try {
            if ((bool) core()->getConfigData('emails.general.notifications.emails.general.notifications.registration')) {
                Mail::queue(new RegistrationNotification($customer));
            }
        } catch (\Exception $e) {
            report($e);
        }

        $this->customerRepository->syncNewRegisteredCustomerInformation($customer);

        auth()->guard('customer')->login($customer);

        Event::dispatch('customer.after.login', $customer);

        session()->flash('success', trans('shop::app.customers.signup-form.success'));

        $intended = session()->pull('url.intended', route('shop.home.index'));

        return redirect($intended);
    }

    /**
     * Resend OTP during signup phone verification.
     */
    public function resendPhoneOtp(): RedirectResponse
    {
        if (! session()->has('signup_pending')) {
            return redirect()->route('shop.customers.register.index');
        }

        $phone = session('signup_pending.phone');

        $otp = (string) random_int(1000, 9999);

        session()->put('signup_otp', $otp);
        session()->put('signup_otp_expires_at', Carbon::now()->addMinutes((int) config('services.smsalert.otp_expiry', 10))->toDateTimeString());

        $sent = $this->smsAlertService->sendOtp($phone, $otp);

        if (! $sent) {
            session()->flash('error', trans('shop::app.customers.otp.send-failed'));
        } else {
            session()->flash('success', trans('shop::app.customers.otp.resent-success'));
        }

        session()->flash('show_otp_modal', true);

        return redirect()->route('shop.customers.register.index');
    }

    /**
     * Method to verify account.
     *
     * @param  string  $token
     * @return Response
     */
    public function verifyAccount($token)
    {
        $customer = $this->customerRepository->findOneByField('token', $token);

        if ($customer) {
            $this->customerRepository->update([
                'is_verified' => 1,
                'token' => null,
            ], $customer->id);

            if ((bool) core()->getConfigData('emails.general.notifications.emails.general.notifications.registration')) {
                Mail::queue(new RegistrationNotification($customer));
            }

            $this->customerRepository->syncNewRegisteredCustomerInformation($customer);

            session()->flash('success', trans('shop::app.customers.signup-form.verified'));
        } else {
            session()->flash('warning', trans('shop::app.customers.signup-form.verify-failed'));
        }

        return redirect()->route('shop.customer.session.index');
    }

    /**
     * Resend verification email.
     *
     * @param  string  $email
     * @return Response
     */
    public function resendVerificationEmail($email)
    {
        $verificationData = [
            'email' => $email,
            'token' => md5(uniqid(rand(), true)),
        ];

        $customer = $this->customerRepository->findOneByField('email', $email);

        $this->customerRepository->update(['token' => $verificationData['token']], $customer->id);

        try {
            Mail::queue(new EmailVerificationNotification($verificationData));

            if (Cookie::has('enable-resend')) {
                Cookie::queue(Cookie::forget('enable-resend'));
            }

            if (Cookie::has('email-for-resend')) {
                Cookie::queue(Cookie::forget('email-for-resend'));
            }
        } catch (\Exception $e) {
            report($e);

            session()->flash('error', trans('shop::app.customers.signup-form.verification-not-sent'));

            return redirect()->back();
        }

        session()->flash('success', trans('shop::app.customers.signup-form.verification-sent'));

        return redirect()->back();
    }
}

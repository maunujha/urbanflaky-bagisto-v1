<?php

namespace Webkul\Shop\Http\Controllers\Customer;

use App\Services\Security\OtpService;
use App\Services\Security\RecaptchaVerifier;
use App\Support\IndianMobile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Shop\Http\Controllers\Controller;

/**
 * OTP login. Codes, attempt counting and send limits live in OtpService
 * (flow "login"); nothing about the code is stored on the customer row.
 */
class OtpController extends Controller
{
    public function __construct(
        protected CustomerRepository $customerRepository,
        protected OtpService $otp,
        protected RecaptchaVerifier $recaptcha,
    ) {}

    /**
     * Send OTP to the given phone number.
     * Redirects back to login page with flash so the OTP boxes appear inline.
     */
    public function send(Request $request): RedirectResponse
    {
        $phone = IndianMobile::normalize($request->input('phone'));

        if (! $phone) {
            return $this->back('error', trans('shop::app.customers.otp.phone-digits'));
        }

        try {
            /* Before the account lookup, so the endpoint can't be scripted to enumerate numbers. */
            if (! $this->recaptcha->passes($request->input('recaptcha_token'), 'submit')) {
                return $this->back('error', 'We couldn’t verify your request. Please refresh the page and try again.');
            }

            $customer = $this->findCustomer($phone);

            if (! $customer) {
                return $this->back('error', trans('shop::app.customers.otp.no-account'));
            }

            if (! $customer->status) {
                return $this->back('warning', trans('shop::app.customers.login-form.not-activated'));
            }

            $result = $this->otp->send('login', $phone, $request);
        } catch (\Throwable $e) {
            return $this->unavailable($e);
        }

        if (! $result['ok']) {
            return $this->back('error', $result['message']);
        }

        session()->put('otp_phone', $phone);
        session()->flash('show_login_otp', true);

        return redirect()->route('shop.customer.session.index');
    }

    /**
     * The code is entered inline on the sign-in page; this legacy URL just
     * sends visitors there (it previously 500'd on a missing method).
     */
    public function showVerify(): RedirectResponse
    {
        if (session()->has('otp_phone')) {
            session()->flash('show_login_otp', true);
        }

        return redirect()->route('shop.customer.session.index');
    }

    /**
     * Verify the OTP and log the customer in.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ], [
            'otp.required' => trans('shop::app.customers.otp.otp-required'),
            'otp.digits'   => trans('shop::app.customers.otp.otp-digits'),
        ]);

        $phone = session()->get('otp_phone');

        if (! $phone) {
            return redirect()->route('shop.customer.session.index');
        }

        try {
            $result = $this->otp->verify('login', $phone, (string) $request->otp, $request);
        } catch (\Throwable $e) {
            return $this->unavailable($e);
        }

        if (! $result['ok']) {
            /* Keep the code boxes open only while the current code can still be used. */
            if ($result['code'] === 'wrong') {
                session()->flash('show_login_otp', true);
            } else {
                session()->forget('otp_phone');
            }

            return $this->back('error', $result['message']);
        }

        $customer = $this->findCustomer($phone);

        if (! $customer || ! $customer->status) {
            session()->forget('otp_phone');

            return $this->back('error', trans('shop::app.customers.otp.no-account'));
        }

        session()->forget('otp_phone');

        auth()->guard('customer')->login($customer);

        Event::dispatch('customer.after.login', $customer);

        $intended = session()->pull('url.intended', null);

        if ($intended) {
            return redirect($intended);
        }

        if (core()->getConfigData('customer.settings.login_options.redirected_to_page') == 'account') {
            return redirect()->route('shop.customers.account.profile.index');
        }

        return redirect()->route('shop.home.index');
    }

    /**
     * Resend OTP to the phone stored in session. No captcha: the session phone
     * can only have been set by a captcha-checked send, and the send limits
     * still apply.
     */
    public function resend(Request $request): RedirectResponse
    {
        $phone = session()->get('otp_phone');

        if (! $phone || ! $this->findCustomer($phone)) {
            return redirect()->route('shop.customer.session.index');
        }

        try {
            $result = $this->otp->send('login', $phone, $request);
        } catch (\Throwable $e) {
            return $this->unavailable($e);
        }

        session()->flash('show_login_otp', true);

        return $result['ok']
            ? $this->back('success', trans('shop::app.customers.otp.resent-success'))
            : $this->back('error', $result['message']);
    }

    protected function findCustomer(string $phone)
    {
        return $this->customerRepository->findOneWhere([
            'phone'      => $phone,
            'channel_id' => core()->getCurrentChannel()->id,
        ]);
    }

    protected function back(string $type, string $message): RedirectResponse
    {
        session()->flash($type, $message);

        return redirect()->route('shop.customer.session.index');
    }

    /* Fail closed (no SMS) if Redis is unavailable. */
    protected function unavailable(\Throwable $e): RedirectResponse
    {
        Log::error('Login OTP unavailable.', ['error' => $e->getMessage()]);

        return $this->back('error', 'Verification is temporarily unavailable. Please try again shortly.');
    }
}

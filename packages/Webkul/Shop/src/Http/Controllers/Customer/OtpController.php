<?php

namespace Webkul\Shop\Http\Controllers\Customer;

use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Customer\Facades\Captcha;
use Webkul\Shop\Http\Controllers\Controller;
use Webkul\Shop\Services\SmsAlertService;

class OtpController extends Controller
{
    public function __construct(
        protected CustomerRepository $customerRepository,
        protected SmsAlertService $smsAlertService
    ) {}

    /**
     * Send OTP to the given phone number.
     * Redirects back to login page with flash so the OTP boxes appear inline.
     */
    public function send(Request $request): RedirectResponse
    {
        $request->validate(
            Captcha::getValidations(['phone' => 'required|digits:10']),
            Captcha::getValidationMessages([
                'phone.required' => trans('shop::app.customers.otp.phone-required'),
                'phone.digits'   => trans('shop::app.customers.otp.phone-digits'),
            ])
        );

        $customer = $this->customerRepository->findOneWhere([
            'phone'      => $request->phone,
            'channel_id' => core()->getCurrentChannel()->id,
        ]);

        if (! $customer) {
            session()->flash('error', trans('shop::app.customers.otp.no-account'));

            return redirect()->route('shop.customer.session.index');
        }

        if (! $customer->status) {
            session()->flash('warning', trans('shop::app.customers.login-form.not-activated'));

            return redirect()->route('shop.customer.session.index');
        }

        $otp = (string) random_int(100000, 999999);

        $customer->update([
            'otp'            => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes((int) config('services.smsalert.otp_expiry', 10)),
        ]);

        /* Reset the failed-attempt counter whenever a fresh OTP is issued. */
        Cache::forget($this->attemptKey($request->phone));

        $sent = $this->smsAlertService->sendOtp($request->phone, $otp);

        if (! $sent) {
            session()->flash('error', trans('shop::app.customers.otp.send-failed'));

            return redirect()->route('shop.customer.session.index');
        }

        session()->put('otp_phone', $request->phone);
        session()->flash('show_login_otp', true);

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

        $customer = $this->customerRepository->findOneWhere([
            'phone'      => $phone,
            'channel_id' => core()->getCurrentChannel()->id,
        ]);

        if (! $customer) {
            session()->flash('error', trans('shop::app.customers.otp.no-account'));

            return redirect()->route('shop.customer.session.index');
        }

        /*
         * Cap wrong guesses per issued OTP. Without this, the OTP stays valid for
         * its whole lifetime and only the IP throttle limits brute force; an
         * attacker could grind the code from rotating IPs. After the cap we burn
         * the OTP so it can never be guessed — the customer must request a new one.
         */
        $attemptKey   = $this->attemptKey($phone);
        $maxAttempts  = 5;

        if ((int) Cache::get($attemptKey, 0) >= $maxAttempts) {
            $customer->update(['otp' => null, 'otp_expires_at' => null]);
            Cache::forget($attemptKey);

            session()->flash('error', trans('shop::app.customers.otp.too-many-attempts'));

            return redirect()->route('shop.customer.session.index');
        }

        if (Carbon::now()->isAfter($customer->otp_expires_at)) {
            session()->flash('error', trans('shop::app.customers.otp.otp-expired'));
            session()->flash('show_login_otp', true);

            return redirect()->route('shop.customer.session.index');
        }

        if (! $customer->otp || ! hash_equals((string) $customer->otp, (string) $request->otp)) {
            Cache::put($attemptKey, (int) Cache::get($attemptKey, 0) + 1, now()->addMinutes(15));

            session()->flash('error', trans('shop::app.customers.otp.invalid-otp'));
            session()->flash('show_login_otp', true);

            return redirect()->route('shop.customer.session.index');
        }

        $customer->update([
            'otp'            => null,
            'otp_expires_at' => null,
        ]);

        Cache::forget($attemptKey);

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
     * Resend OTP to the phone stored in session.
     */
    public function resend(): RedirectResponse
    {
        $phone = session()->get('otp_phone');

        if (! $phone) {
            return redirect()->route('shop.customer.session.index');
        }

        $customer = $this->customerRepository->findOneWhere([
            'phone'      => $phone,
            'channel_id' => core()->getCurrentChannel()->id,
        ]);

        if (! $customer) {
            return redirect()->route('shop.customer.session.index');
        }

        $otp = (string) random_int(100000, 999999);

        $customer->update([
            'otp'            => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes((int) config('services.smsalert.otp_expiry', 10)),
        ]);

        /* A fresh OTP clears the failed-attempt counter. */
        Cache::forget($this->attemptKey($phone));

        $sent = $this->smsAlertService->sendOtp($phone, $otp);

        if (! $sent) {
            session()->flash('error', trans('shop::app.customers.otp.send-failed'));
        } else {
            session()->flash('success', trans('shop::app.customers.otp.resent-success'));
        }

        session()->flash('show_login_otp', true);

        return redirect()->route('shop.customer.session.index');
    }

    /**
     * Cache key holding the per-phone failed-verification counter.
     */
    protected function attemptKey(string $phone): string
    {
        return 'login_otp_attempts:'.$phone;
    }
}

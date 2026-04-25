<?php

namespace Webkul\Shop\Http\Controllers\Customer;

use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Customer\Repositories\CustomerRepository;
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
        $request->validate([
            'phone' => 'required|digits:10',
        ], [
            'phone.required' => trans('shop::app.customers.otp.phone-required'),
            'phone.digits'   => trans('shop::app.customers.otp.phone-digits'),
        ]);

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

        $otp = (string) random_int(1000, 9999);

        $customer->update([
            'otp'            => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes((int) config('services.smsalert.otp_expiry', 10)),
        ]);

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
            'otp' => 'required|digits:4',
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

        if ($customer->otp !== $request->otp) {
            session()->flash('error', trans('shop::app.customers.otp.invalid-otp'));
            session()->flash('show_login_otp', true);

            return redirect()->route('shop.customer.session.index');
        }

        if (Carbon::now()->isAfter($customer->otp_expires_at)) {
            session()->flash('error', trans('shop::app.customers.otp.otp-expired'));
            session()->flash('show_login_otp', true);

            return redirect()->route('shop.customer.session.index');
        }

        $customer->update([
            'otp'            => null,
            'otp_expires_at' => null,
        ]);

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

        $otp = (string) random_int(1000, 9999);

        $customer->update([
            'otp'            => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes((int) config('services.smsalert.otp_expiry', 10)),
        ]);

        $sent = $this->smsAlertService->sendOtp($phone, $otp);

        if (! $sent) {
            session()->flash('error', trans('shop::app.customers.otp.send-failed'));
        } else {
            session()->flash('success', trans('shop::app.customers.otp.resent-success'));
        }

        session()->flash('show_login_otp', true);

        return redirect()->route('shop.customer.session.index');
    }
}

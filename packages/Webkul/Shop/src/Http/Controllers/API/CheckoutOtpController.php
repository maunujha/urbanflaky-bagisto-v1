<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Shop\Services\SmsAlertService;

class CheckoutOtpController extends APIController
{
    public function __construct(protected SmsAlertService $smsAlertService) {}

    /**
     * Send OTP to the given phone number.
     * If logged-in user's profile phone matches, mark as verified immediately.
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate(['phone' => 'required|digits:10']);

        $phone = $request->phone;

        /* Logged-in user whose profile phone matches → auto-verified, no OTP needed */
        if (auth()->guard('customer')->check()) {
            $profilePhone = auth()->guard('customer')->user()->phone;

            if ($profilePhone && $profilePhone === $phone) {
                session(['checkout_phone_verified' => true, 'checkout_otp_phone' => $phone]);

                return response()->json(['verified' => true]);
            }
        }

        $otp = (string) random_int(100000, 999999);

        session([
            'checkout_otp'            => $otp,
            'checkout_otp_phone'      => $phone,
            'checkout_otp_expires_at' => now()->addMinutes((int) config('services.smsalert.otp_expiry', 10))->toDateTimeString(),
            'checkout_phone_verified' => false,
        ]);

        if (! $this->smsAlertService->sendOtp($phone, $otp)) {
            return response()->json(['message' => 'Failed to send OTP. Please try again.'], 500);
        }

        return response()->json(['message' => 'OTP sent to ' . substr($phone, 0, 2) . 'xxxxxxxx.']);
    }

    /**
     * Verify OTP entered by the user.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|digits:10',
            'otp'   => 'required|digits:6',
        ]);

        $sessionOtp   = session('checkout_otp');
        $sessionPhone = session('checkout_otp_phone');
        $expiry       = session('checkout_otp_expires_at');

        if (! $sessionOtp || ! $sessionPhone) {
            return response()->json(['message' => 'Session expired. Please request a new OTP.'], 422);
        }

        if ($sessionPhone !== $request->phone) {
            return response()->json(['message' => 'Phone number mismatch. Request a new OTP.'], 422);
        }

        if (now()->toDateTimeString() > $expiry) {
            return response()->json(['message' => 'OTP expired. Please request a new one.'], 422);
        }

        if ($sessionOtp !== $request->otp) {
            return response()->json(['message' => 'Incorrect OTP. Please try again.'], 422);
        }

        session()->forget(['checkout_otp', 'checkout_otp_expires_at']);
        session(['checkout_phone_verified' => true]);

        $customer = app(\App\Services\OtpCustomerService::class)
                        ->handleVerifiedPhone($phone);

        session([
            'verified_phone'       => $phone,
            'checkout_customer_id' => $customer->id,
        ]);

        return response()->json([
            'message'          => 'Mobile number verified successfully.',
            'customer_id'      => $customer->id,
            'is_new_customer'  => $customer->wasRecentlyCreated,
        ]);
    }
}

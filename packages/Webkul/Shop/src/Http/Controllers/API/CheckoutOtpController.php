<?php

namespace Webkul\Shop\Http\Controllers\API;

use App\Services\OtpCustomerService;
use App\Services\Security\OtpService;
use App\Services\Security\RecaptchaVerifier;
use App\Support\IndianMobile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutOtpController extends APIController
{
    public function __construct(
        protected OtpService $otp,
        protected RecaptchaVerifier $recaptcha,
    ) {}

    /**
     * Send OTP to the given phone number.
     * If logged-in user's profile phone matches, mark as verified immediately.
     */
    public function send(Request $request): JsonResponse
    {
        $phone = IndianMobile::normalize($request->input('phone'));

        if (! $phone) {
            return response()->json(['message' => 'Enter a valid 10-digit Indian mobile number.'], 422);
        }

        /* Logged-in user whose profile phone matches → auto-verified, no SMS sent */
        if (auth()->guard('customer')->check()) {
            $profilePhone = IndianMobile::normalize(auth()->guard('customer')->user()->phone);

            if ($profilePhone && $profilePhone === $phone) {
                session()->forget('checkout_otp_challenge');
                session(['checkout_phone_verified' => true, 'checkout_otp_phone' => $phone]);

                return response()->json(['verified' => true]);
            }
        }

        try {
            if (! $this->recaptcha->passes($request->input('recaptcha_token'), 'otp_send')) {
                return response()->json(['message' => 'We couldn’t verify your request. Please refresh the page and try again.'], 422);
            }

            $result = $this->otp->send('checkout', $phone, $request);
        } catch (\Throwable $e) {
            return $this->unavailable($e);
        }

        return response()->json(['message' => $result['message']], $result['status']);
    }

    /**
     * Verify OTP entered by the user.
     */
    public function verify(Request $request): JsonResponse
    {
        $phone = IndianMobile::normalize($request->input('phone'));
        $code = (string) $request->input('otp');

        if (! $phone || ! preg_match('/^\d{6}$/', $code)) {
            return response()->json(['message' => 'Enter the 6-digit OTP sent to your mobile.'], 422);
        }

        try {
            $result = $this->otp->verify('checkout', $phone, $code, $request);
        } catch (\Throwable $e) {
            return $this->unavailable($e);
        }

        if (! $result['ok']) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        $customer = app(OtpCustomerService::class)->handleVerifiedPhone($phone);

        session([
            'verified_phone'       => $phone,
            'checkout_customer_id' => $customer->id,
        ]);

        return response()->json([
            'message'          => $result['message'],
            'customer_id'      => $customer->id,
            'is_new_customer'  => $customer->wasRecentlyCreated,
        ]);
    }

    /* Fail closed (no SMS) if Redis is unavailable, with a friendly message. */
    private function unavailable(\Throwable $e): JsonResponse
    {
        Log::error('Checkout OTP unavailable.', ['error' => $e->getMessage()]);

        return response()->json(['message' => 'Verification is temporarily unavailable. Please try again shortly.'], 503);
    }
}

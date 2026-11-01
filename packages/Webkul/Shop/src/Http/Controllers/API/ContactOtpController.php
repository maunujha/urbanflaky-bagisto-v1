<?php

namespace Webkul\Shop\Http\Controllers\API;

use App\Services\Security\OtpService;
use App\Services\Security\RecaptchaVerifier;
use App\Support\IndianMobile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactOtpController extends APIController
{
    public function __construct(
        protected OtpService $otp,
        protected RecaptchaVerifier $recaptcha,
    ) {}

    /**
     * Send OTP to the given phone for contact-form verification.
     */
    public function send(Request $request): JsonResponse
    {
        $phone = IndianMobile::normalize($request->input('phone'));

        if (! $phone) {
            return response()->json(['message' => 'Enter a valid 10-digit Indian mobile number.'], 422);
        }

        return $this->respond(function () use ($phone, $request) {
            if (! $this->recaptcha->passes($request->input('recaptcha_token'), 'otp_send')) {
                return ['status' => 422, 'message' => 'We couldn’t verify your request. Please refresh the page and try again.'];
            }

            return $this->otp->send('contact', $phone, $request);
        });
    }

    /**
     * Verify the OTP entered by the user.
     */
    public function verify(Request $request): JsonResponse
    {
        $phone = IndianMobile::normalize($request->input('phone'));
        $code = (string) $request->input('otp');

        if (! $phone || ! preg_match('/^\d{6}$/', $code)) {
            return response()->json(['message' => 'Enter the 6-digit OTP sent to your mobile.'], 422);
        }

        return $this->respond(fn () => $this->otp->verify('contact', $phone, $code, $request));
    }

    /* Fail closed (no SMS) if Redis is unavailable, with a friendly message. */
    private function respond(callable $action): JsonResponse
    {
        try {
            $result = $action();
        } catch (\Throwable $e) {
            Log::error('Contact OTP unavailable.', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Verification is temporarily unavailable. Please try again shortly.'], 503);
        }

        return response()->json(['message' => $result['message']], $result['status']);
    }
}

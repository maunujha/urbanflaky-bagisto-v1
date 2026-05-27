<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Shop\Services\SmsAlertService;

class ContactOtpController extends APIController
{
    public function __construct(protected SmsAlertService $smsAlertService) {}

    /**
     * Send OTP to the given phone for contact-form verification.
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate(['phone' => 'required|digits:10']);

        $phone = $request->phone;

        $otp = (string) random_int(100000, 999999);

        session([
            'contact_otp'            => $otp,
            'contact_otp_phone'      => $phone,
            'contact_otp_expires_at' => now()->addMinutes((int) config('services.smsalert.otp_expiry', 10))->toDateTimeString(),
            'contact_phone_verified' => false,
        ]);

        if (! $this->smsAlertService->sendOtp($phone, $otp)) {
            return response()->json(['message' => 'Failed to send OTP. Please try again.'], 500);
        }

        return response()->json(['message' => 'OTP sent to ' . substr($phone, 0, 2) . 'xxxxxxxx.']);
    }

    /**
     * Verify the OTP entered by the user.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|digits:10',
            'otp'   => 'required|digits:6',
        ]);

        $sessionOtp   = session('contact_otp');
        $sessionPhone = session('contact_otp_phone');
        $expiry       = session('contact_otp_expires_at');

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

        session()->forget(['contact_otp', 'contact_otp_expires_at']);
        session(['contact_phone_verified' => true]);

        return response()->json(['message' => 'Mobile number verified successfully.']);
    }
}

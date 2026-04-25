<?php

namespace Webkul\Shop\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsAlertService
{
    private string $apiKey;
    private string $sender;
    private string $endpoint = 'https://www.smsalert.co.in/api/push.json';

    // DLT-registered template: "Use this OTP {#var#} to verify your account with {#var#}. Valid only for 10 minutes.-- Powered By Gabha Enterprise"
    private string $template = 'Use this OTP {#var#} to verify your account with {#var#}. Valid only for 10 minutes.-- Powered By Gabha Enterprise';

    public function __construct()
    {
        $this->apiKey  = config('services.smsalert.apikey');
        $this->sender  = config('services.smsalert.sender');
    }

    public function sendOtp(string $mobile, string $otp): bool
    {
        $appName = config('app.name', 'Urban Flaky');

        // Replace both {#var#} placeholders in order: first = OTP, second = app name
        $message = preg_replace('/\{#var#\}/', $otp, $this->template, 1);
        $message = preg_replace('/\{#var#\}/', $appName, $message, 1);

        $payload = [
            'apikey'      => $this->apiKey,
            'sender'      => $this->sender,
            'mobileno'    => $mobile,
            'text'        => $message,
            'template_id' => config('services.smsalert.template_id'),
        ];

        try {
            $response = Http::asForm()->post($this->endpoint, $payload);

            $data = $response->json();

            $success = (isset($data['ErrorCode']) && $data['ErrorCode'] === '000')
                || (isset($data['status']) && strtolower($data['status']) === 'success');

            if ($success) {
                return true;
            }

            Log::error('SmsAlert OTP send failed', ['response' => $data, 'mobile' => $mobile]);

            return false;
        } catch (\Exception $e) {
            Log::error('SmsAlert exception', ['error' => $e->getMessage(), 'mobile' => $mobile]);

            return false;
        }
    }
}

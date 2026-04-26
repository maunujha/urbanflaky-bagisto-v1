<?php

namespace Webkul\Shop\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsAlertService
{
    private string $apiKey;
    private string $sender;
    private string $endpoint = 'https://www.smsalert.co.in/api/push.json';

    /* ── Active DLT-registered templates ── */

    // OTP: {otp} {app}  — ID: 1007672951423952579
    private string $tplOtp = 'Use this OTP {#var#} to verify your account with {#var#}. Valid only for 10 minutes.-- Powered By Gabha Enterprise';

    // Welcome: no variables  — ID: 1007151149490178574
    private string $tplWelcome = 'Welcome to Urbanflaky – a brand by Gabha Enterprise! Thank you for subscribing – we\'re excited to have you on board. Stay tuned for exclusive offers, latest drops, and more!';

    // Registration: {name} {app}  — ID: 1007718004435793169
    private string $tplRegistration = 'Hello {#var#} thank you for registering with {#var#}. Powered by: Gabha Enterprise';

    // Order placed: {name} {order_id} {app}  — ID: 1007005412973027038
    private string $tplOrderPlaced = 'Dear {#var#}, thank you for placing your order {#var#} with {#var#}. Your order will be delivered soon. Powered by Gabha Enterprise';

    // Order delivered: {name} {order_id}  — ID: 1007207950494402730
    private string $tplOrderDelivered = '{#var#}: status of order {#var#} with urbanflaky has been changed to delivered. Powered by: Gabha Enterprise';

    // Order refunded: {name} {order_id}  — ID: 1007891099003698239
    private string $tplOrderRefunded = 'Hello {#var#}, status of your order {#var#} with Urbanflaky has been changed to refunded. Powered by: Gabha Enterprise';

    // Abandoned cart: {name} {link}  — ID: 1007331906292273181
    private string $tplAbandonedCart = 'Hey {#var#}, We noticed you could not complete your order. Click on the link below to place your order. Shop Now - {#var#} Urbanflaky a brand by Gabha Enterprise';

    // Admin — New inquiry: {name} {email}  — ID: 1007882838342498936
    private string $tplAdminInquiry = 'You\'ve received a new inquiry from {#var#} at {#var#}. Please check your dashboard for the details. Powered By- Gabha Enterprise';

    /* ── Pending re-registration (typo in DLT name — will be added back once fixed) ──
       Order shipped       1007713358799285654  → re-register with Urbanflaky
       Order cancelled     1007526607518806035  → re-register with Urbanflaky
       Admin signup        1007523576236221697  → re-register with Urbanflaky
       Admin order status  1007328285923273144  → re-register with Urbanflaky
       Admin new order     1007242853907840382  → re-register with Urbanflaky
    ── */

    public function __construct()
    {
        $this->apiKey = config('services.smsalert.apikey');
        $this->sender = config('services.smsalert.sender');
    }

    /* ════════════════════════════════════════════
       Customer SMS methods
    ════════════════════════════════════════════ */

    public function sendOtp(string $mobile, string $otp): bool
    {
        $message = $this->build($this->tplOtp, [$otp, config('app.name')]);
        return $this->send($mobile, $message, 'tpl_otp');
    }

    public function sendWelcome(string $mobile): bool
    {
        return $this->send($mobile, $this->tplWelcome, 'tpl_welcome');
    }

    public function sendRegistration(string $mobile, string $name): bool
    {
        $message = $this->build($this->tplRegistration, [$name, config('app.name')]);
        return $this->send($mobile, $message, 'tpl_registration');
    }

    public function sendOrderPlaced(string $mobile, string $name, string $orderId): bool
    {
        $message = $this->build($this->tplOrderPlaced, [$name, $orderId, config('app.name')]);
        return $this->send($mobile, $message, 'tpl_order_placed');
    }

    public function sendOrderDelivered(string $mobile, string $name, string $orderId): bool
    {
        $message = $this->build($this->tplOrderDelivered, [$name, $orderId]);
        return $this->send($mobile, $message, 'tpl_order_delivered');
    }

    public function sendOrderRefunded(string $mobile, string $name, string $orderId): bool
    {
        $message = $this->build($this->tplOrderRefunded, [$name, $orderId]);
        return $this->send($mobile, $message, 'tpl_order_refunded');
    }

    public function sendAbandonedCart(string $mobile, string $name, string $cartUrl): bool
    {
        $message = $this->build($this->tplAbandonedCart, [$name, $cartUrl]);
        return $this->send($mobile, $message, 'tpl_abandoned_cart');
    }

    /* ════════════════════════════════════════════
       Admin SMS methods
    ════════════════════════════════════════════ */

    public function sendAdminInquiry(string $fromName, string $fromEmail): bool
    {
        $admin = config('services.smsalert.admin_phone');
        if (! $admin) return false;
        $message = $this->build($this->tplAdminInquiry, [$fromName, $fromEmail]);
        return $this->send($admin, $message, 'tpl_admin_inquiry');
    }

    /* ════════════════════════════════════════════
       Private helpers
    ════════════════════════════════════════════ */

    private function build(string $template, array $vars): string
    {
        foreach ($vars as $value) {
            $template = preg_replace('/\{#var#\}/', $value, $template, 1);
        }
        return $template;
    }

    private function send(string $mobile, string $message, string $templateKey): bool
    {
        $payload = [
            'apikey'      => $this->apiKey,
            'sender'      => $this->sender,
            'mobileno'    => $mobile,
            'text'        => $message,
            'template_id' => config('services.smsalert.' . $templateKey),
        ];

        try {
            $response = Http::asForm()->post($this->endpoint, $payload);
            $data     = $response->json();

            $success = (isset($data['ErrorCode']) && $data['ErrorCode'] === '000')
                || (isset($data['status']) && strtolower($data['status']) === 'success');

            if ($success) {
                return true;
            }

            Log::error('SmsAlert send failed', [
                'template' => $templateKey,
                'mobile'   => $mobile,
                'response' => $data,
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('SmsAlert exception', [
                'template' => $templateKey,
                'mobile'   => $mobile,
                'error'    => $e->getMessage(),
            ]);

            return false;
        }
    }
}

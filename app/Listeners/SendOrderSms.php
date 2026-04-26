<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Webkul\Shop\Services\SmsAlertService;

class SendOrderSms implements ShouldQueue
{
    public int $tries = 2;

    public function __construct(protected SmsAlertService $smsAlertService) {}

    public function handle($order): void
    {
        $phone = $order->shipping_address?->phone
            ?? $order->billing_address?->phone
            ?? $order->customer?->phone;

        if (! $phone) {
            return;
        }

        $customerName = trim(
            ($order->customer_first_name ?? $order->billing_address?->first_name ?? 'Customer')
            . ' ' .
            ($order->customer_last_name  ?? $order->billing_address?->last_name  ?? '')
        );

        $this->smsAlertService->sendOrderConfirmation(
            mobile:       $phone,
            customerName: $customerName,
            orderId:      $order->increment_id,
        );
    }
}

<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Webkul\Shop\Services\SmsAlertService;

class SendCancellationSms implements ShouldQueue
{
    public int $tries = 2;

    public function __construct(protected SmsAlertService $sms) {}

    public function handle($order): void
    {
        $phone = $order->shipping_address?->phone ?? $order->billing_address?->phone;

        if (! $phone) return;

        $name = trim($order->customer_first_name . ' ' . $order->customer_last_name) ?: 'Customer';

        $this->sms->sendOrderCancelled($phone, $name, $order->increment_id);

        $this->sms->sendAdminOrderStatus($order->increment_id, 'Cancelled');
    }
}

<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Webkul\Shop\Services\SmsAlertService;

class SendDeliveredSms implements ShouldQueue
{
    public int $tries = 2;

    public function __construct(protected SmsAlertService $smsAlertService) {}

    /**
     * Fires on shiprocket.order.delivered, which a retried milestone can
     * repeat: one SMS per order.
     */
    public function handle($order): void
    {
        $sentKey = 'sms:delivered:'.$order->id;

        if (Cache::has($sentKey)) {
            return;
        }

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

        if ($this->smsAlertService->sendOrderDelivered(
            mobile:  $phone,
            name:    $customerName,
            orderId: $order->increment_id,
        )) {
            Cache::put($sentKey, true, now()->addDays(60));
        }
    }
}

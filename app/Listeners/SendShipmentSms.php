<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Webkul\Shop\Services\SmsAlertService;

class SendShipmentSms implements ShouldQueue
{
    public int $tries = 2;

    public function __construct(protected SmsAlertService $sms) {}

    public function handle($shipment): void
    {
        $order = $shipment->order;
        $phone = $order->shipping_address?->phone ?? $order->billing_address?->phone;

        if (! $phone || ! $order) return;

        $name = trim($order->customer_first_name . ' ' . $order->customer_last_name) ?: 'Customer';

        $this->sms->sendOrderShipped($phone, $name, $order->increment_id);
    }
}

<?php

namespace Gabha\Notifications\Listeners;

use App\Services\Shiprocket\ShipmentStage;
use Gabha\Notifications\Mail\ShipmentUpdate;
use Gabha\Notifications\Services\AutomationSettings;
use Gabha\Notifications\Services\EmailDispatcher;
use Webkul\Sales\Models\Order;

/**
 * Fires on shiprocket.shipment.stage_changed. "Shipped" is Bagisto's own
 * shipment email; this covers what happens after it.
 */
class SendShipmentStageEmail
{
    /** Courier stage => email stage (and admin switch name). */
    public const STAGES = [
        ShipmentStage::AWB_ASSIGNED     => 'processing',
        ShipmentStage::OUT_FOR_DELIVERY => 'out_for_delivery',
        ShipmentStage::DELIVERED        => 'delivered',
        ShipmentStage::UNDELIVERED      => 'delivery_failed',
        ShipmentStage::RTO_INITIATED    => 'rto',
    ];

    /** Stages that can genuinely repeat (re-attempts): at most once a day. */
    protected const DAILY = ['out_for_delivery', 'delivery_failed'];

    public function __construct(
        protected EmailDispatcher $dispatcher,
        protected AutomationSettings $settings,
    ) {}

    public function handle(Order $order, ?string $from, string $to): void
    {
        $stage = self::STAGES[$to] ?? null;

        if (! $stage || ! $order->customer_email || ! $this->settings->enabled('shipping.'.$stage)) {
            return;
        }

        if ($order->status === Order::STATUS_CANCELED) {
            return;
        }

        /* Packed minutes after ordering: the confirmation already says it all. */
        if ($stage === 'processing'
            && $order->created_at->gt(now()->subMinutes((int) config('notifications.processing_min_age_minutes', 60)))) {
            return;
        }

        $key = 'shipment.'.$stage.':'.$order->id.(in_array($stage, self::DAILY, true) ? ':'.now()->toDateString() : '');

        try {
            $this->dispatcher->send(new ShipmentUpdate($order, $stage), 'shipment.'.$stage, $key, $order);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

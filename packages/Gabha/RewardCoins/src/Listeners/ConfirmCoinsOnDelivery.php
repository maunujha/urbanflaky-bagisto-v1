<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Listeners;

use Gabha\RewardCoins\Jobs\ConfirmCoinsForOrderDelivery;
use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Services\CoinDeliveryService;

/**
 * Opens the post-delivery return window on an order's pending coins.
 *
 * Two entry points, one outcome:
 *  - {@see self::handle()} on `sales.order.update-status.after` when the order
 *    reaches a `confirm_on_statuses` status (admin / Bagisto completion);
 *  - {@see self::handleDelivered()} on the carrier delivery event fired by the
 *    Shiprocket tracking webhook.
 *
 * Neither confirms coins: they only stamp `available_at` = delivery +
 * pending_confirmation_days; `reward-coins:confirm-available` promotes them
 * once that passes. Whether the order really is delivered is decided when the
 * queued job runs, after the surrounding transaction commits, by
 * {@see CoinDeliveryService::isDelivered()} — so the transient `completed`
 * Bagisto sets while a shipment is being created never opens a window.
 *
 * Only the order id and the unlock time are queued (the order model's relation
 * graph is not reliably serializable).
 */
class ConfirmCoinsOnDelivery
{
    public function __construct(
        private readonly CoinDeliveryService $delivery,
    ) {
    }

    /**
     * Handle an order status change.
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @return void
     */
    public function handle($order): void
    {
        if (! in_array($order->status, (array) config('reward_coins.confirm_on_statuses', ['completed']), true)) {
            return;
        }

        $this->dispatch($order);
    }

    /**
     * Handle a carrier "delivered" notification.
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @return void
     */
    public function handleDelivered($order): void
    {
        $this->dispatch($order);
    }

    /**
     * Queue the window stamp for the order.
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @return void
     */
    private function dispatch($order): void
    {
        if (! CoinSetting::isEnabled() || empty($order->customer_id)) {
            return;
        }

        ConfirmCoinsForOrderDelivery::dispatch((int) $order->id, $this->delivery->availableAtFor());
    }
}

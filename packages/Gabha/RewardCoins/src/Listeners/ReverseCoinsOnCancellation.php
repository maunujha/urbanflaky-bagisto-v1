<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Listeners;

use Gabha\RewardCoins\Jobs\ReverseCoinsForOrder;
use Gabha\RewardCoins\Models\CoinSetting;

/**
 * Reverses coin movements when an order is cancelled or closed
 * (sales.order.update-status.after): redeemed coins are restored and any
 * still-pending earned coins are cancelled.
 *
 * Both admin cancellation and the Shiprocket cancellation webhook flow through
 * OrderRepository::cancel() → updateOrderStatus(), so both fire this event.
 *
 * Runs synchronously itself (it only reads $order->status/$order->id, both
 * scalar columns), then queues the actual reversal as
 * {@see ReverseCoinsForOrder}, carrying only the order id. Queueing the live
 * $order model directly used to crash with "Serialization of 'Closure' is
 * not allowed" — something in core Bagisto's order relation graph isn't
 * reliably serializable. Queueing just the id sidesteps the crash while
 * keeping the reversal off the cancellation request's critical path.
 */
class ReverseCoinsOnCancellation
{
    /**
     * Handle the order status-change event.
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @return void
     */
    public function handle($order): void
    {
        if (! CoinSetting::isEnabled()) {
            return;
        }

        $reverseStatuses = (array) config('reward_coins.reverse_on_statuses', ['canceled', 'closed']);

        if (! in_array($order->status, $reverseStatuses, true)) {
            return;
        }

        ReverseCoinsForOrder::dispatch((int) $order->id);
    }
}

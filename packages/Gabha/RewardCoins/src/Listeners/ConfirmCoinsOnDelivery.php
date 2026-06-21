<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Listeners;

use Gabha\RewardCoins\Jobs\ConfirmCoinsForOrderDelivery;
use Gabha\RewardCoins\Models\CoinSetting;
use Illuminate\Support\Carbon;

/**
 * Opens the post-delivery return window on a customer's pending coins once their
 * order reaches a "delivered" status (sales.order.update-status.after).
 *
 * Bagisto core has no `delivered` status — in this store delivery maps to
 * `completed` (see config `reward_coins.confirm_on_statuses`). Note: the
 * Shiprocket *delivery* webhook saves the order directly and does not fire this
 * event; admin status changes to `completed` do.
 *
 * This listener no longer confirms coins. It only stamps `available_at` =
 * delivery + pending_confirmation_days. The coins stay `pending` (not spendable)
 * until that window elapses, when {@see \Gabha\RewardCoins\Console\ConfirmAvailableCoinsCommand}
 * promotes them. Cancellation/void of pending coins is handled separately by
 * {@see ReverseCoinsOnCancellation}.
 *
 * Runs synchronously itself (it only reads $order->status/$order->id, both
 * scalar columns), then queues the actual write as
 * {@see ConfirmCoinsForOrderDelivery}, carrying only the order id and the
 * computed unlock timestamp. Queueing the live $order model directly used to
 * crash with "Serialization of 'Closure' is not allowed" — something in core
 * Bagisto's order relation graph isn't reliably serializable. Queueing just
 * these primitives sidesteps the crash while keeping the write off this
 * request's critical path.
 */
class ConfirmCoinsOnDelivery
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

        $confirmStatuses = (array) config('reward_coins.confirm_on_statuses', ['completed']);

        if (! in_array($order->status, $confirmStatuses, true)) {
            return;
        }

        // Return-window length (days) before delivered coins become spendable.
        $windowDays = (int) CoinSetting::active()->pending_confirmation_days;

        $availableAt = $windowDays > 0
            ? Carbon::now()->addDays($windowDays)
            : Carbon::now();

        ConfirmCoinsForOrderDelivery::dispatch((int) $order->id, $availableAt);
    }
}

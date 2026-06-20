<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Listeners;

use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Repositories\Contracts\CoinTransactionRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

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
 * Deliberately NOT queued (unlike most event listeners elsewhere in this
 * package): the $order payload this event dispatches can carry a
 * non-serializable closure somewhere in its loaded relation graph (root cause
 * not in this package - surfaced as "Serialization of 'Closure' is not
 * allowed" when Laravel builds the queued job, before handle() ever runs, and
 * crashes the whole event dispatch for every listener on this event, not just
 * this one). Running inline avoids the queue's serialize/unserialize
 * round-trip entirely; stamping a timestamp is a single lightweight DB update.
 */
class ConfirmCoinsOnDelivery
{
    public function __construct(
        private readonly CoinTransactionRepositoryInterface $transactions,
    ) {
    }

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

        try {
            // Return-window length (days) before delivered coins become spendable.
            $windowDays = (int) CoinSetting::active()->pending_confirmation_days;

            $availableAt = $windowDays > 0
                ? Carbon::now()->addDays($windowDays)
                : Carbon::now();

            // Idempotent: only stamps rows not already carrying an unlock time, so a
            // repeated status save never resets a running window.
            $this->transactions->stampAvailableAt((int) $order->id, $availableAt);
        } catch (Throwable $e) {
            Log::error('RewardCoins: failed to open the coin return window on delivery.', [
                'order_id' => $order->id ?? null,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Listeners;

use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Services\CoinRedemptionService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reverses coin movements when an order is cancelled or closed
 * (sales.order.update-status.after): redeemed coins are restored and any
 * still-pending earned coins are cancelled.
 *
 * Both admin cancellation and the Shiprocket cancellation webhook flow through
 * OrderRepository::cancel() → updateOrderStatus(), so both fire this event.
 *
 * Deliberately NOT queued (unlike the sibling RewardCoins listeners): the
 * $order payload this event dispatches can carry a non-serializable closure
 * somewhere in its loaded relation graph (root cause not in this package -
 * surfaced as "Serialization of 'Closure' is not allowed" when Laravel
 * builds the queued job, before handle() ever runs). Running inline avoids
 * the queue's serialize/unserialize round-trip entirely; the reversal work
 * itself is a couple of lightweight DB updates, not worth the queue risk.
 */
class ReverseCoinsOnCancellation
{
    public function __construct(
        private readonly CoinRedemptionService $redemptionService,
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

        $reverseStatuses = (array) config('reward_coins.reverse_on_statuses', ['canceled', 'closed']);

        if (! in_array($order->status, $reverseStatuses, true)) {
            return;
        }

        try {
            $this->redemptionService->reverse((int) $order->id);
        } catch (Throwable $e) {
            Log::error('RewardCoins: failed to reverse coins on cancellation.', [
                'order_id' => $order->id ?? null,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}

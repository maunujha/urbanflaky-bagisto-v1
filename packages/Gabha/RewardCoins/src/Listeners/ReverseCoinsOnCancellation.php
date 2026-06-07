<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Listeners;

use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Services\CoinRedemptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reverses coin movements when an order is cancelled or closed
 * (sales.order.update-status.after): redeemed coins are restored and any
 * still-pending earned coins are cancelled.
 *
 * Both admin cancellation and the Shiprocket cancellation webhook flow through
 * OrderRepository::cancel() → updateOrderStatus(), so both fire this event.
 */
class ReverseCoinsOnCancellation implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly CoinRedemptionService $redemptionService,
    ) {
    }

    /**
     * Route this listener onto the dedicated coins queue.
     *
     * @return string
     */
    public function viaQueue(): string
    {
        return (string) config('reward_coins.queue', 'coins');
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

        $this->redemptionService->reverse((int) $order->id);
    }

    /**
     * Log a failed run.
     *
     * @param  mixed  $order
     * @param  Throwable  $e
     * @return void
     */
    public function failed($order, Throwable $e): void
    {
        Log::error('RewardCoins: failed to reverse coins on cancellation.', [
            'order_id' => $order->id ?? null,
            'error'    => $e->getMessage(),
        ]);
    }
}

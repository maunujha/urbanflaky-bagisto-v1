<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Jobs;

use Gabha\RewardCoins\Repositories\Contracts\CoinTransactionRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued: opens the post-delivery return window on a delivered order's
 * pending coins.
 *
 * Carries only the order id and the already-computed unlock timestamp,
 * never the Bagisto order model — see
 * {@see \Gabha\RewardCoins\Listeners\ConfirmCoinsOnDelivery} for why the
 * order model itself isn't safe to serialize onto a queue.
 */
class ConfirmCoinsForOrderDelivery implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public readonly int $orderId,
        public readonly Carbon $availableAt,
    ) {
        $this->onQueue((string) config('reward_coins.queue', 'coins'));
    }

    public function handle(CoinTransactionRepositoryInterface $transactions): void
    {
        // Idempotent: only stamps rows not already carrying an unlock time, so a
        // repeated status save never resets a running window.
        $transactions->stampAvailableAt($this->orderId, $this->availableAt);
    }

    public function failed(Throwable $e): void
    {
        Log::error('RewardCoins: failed to open the coin return window on delivery.', [
            'order_id' => $this->orderId,
            'error'    => $e->getMessage(),
        ]);
    }
}

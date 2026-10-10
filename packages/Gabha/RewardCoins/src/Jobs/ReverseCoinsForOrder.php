<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Jobs;

use Gabha\RewardCoins\Services\CoinOrderReversalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued: reconciles a cancelled/closed order's coins.
 *
 * Dispatched after the surrounding transaction commits and fully idempotent
 * ({@see CoinOrderReversalService::sync()} posts only what is still owed), so
 * a retry, or the refund listener having already settled the order, is safe.
 *
 * Carries only the order id (an int), never the Bagisto order model — see
 * {@see \Gabha\RewardCoins\Listeners\ReverseCoinsOnCancellation}.
 */
class ReverseCoinsForOrder implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public readonly int $orderId,
    ) {
        $this->onQueue((string) config('reward_coins.queue', 'coins'));
        $this->afterCommit();
    }

    public function handle(CoinOrderReversalService $reversal): void
    {
        $reversal->sync($this->orderId, 'order-status');
    }

    public function failed(Throwable $e): void
    {
        Log::error('RewardCoins: failed to reverse coins on cancellation.', [
            'order_id' => $this->orderId,
            'error'    => $e->getMessage(),
        ]);
    }
}

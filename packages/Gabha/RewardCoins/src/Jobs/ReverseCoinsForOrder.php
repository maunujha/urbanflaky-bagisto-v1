<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Jobs;

use Gabha\RewardCoins\Services\CoinRedemptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued: reverses coin movements for a cancelled/closed order.
 *
 * Carries only the order id (an int), never the Bagisto order model — see
 * {@see \Gabha\RewardCoins\Listeners\ReverseCoinsOnCancellation} for why the
 * order model itself isn't safe to serialize onto a queue.
 */
class ReverseCoinsForOrder implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public readonly int $orderId,
    ) {
        $this->onQueue((string) config('reward_coins.queue', 'coins'));
    }

    public function handle(CoinRedemptionService $redemptionService): void
    {
        $redemptionService->reverse($this->orderId);
    }

    public function failed(Throwable $e): void
    {
        Log::error('RewardCoins: failed to reverse coins on cancellation.', [
            'order_id' => $this->orderId,
            'error'    => $e->getMessage(),
        ]);
    }
}

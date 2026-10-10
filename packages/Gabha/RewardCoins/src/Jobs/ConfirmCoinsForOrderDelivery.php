<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Jobs;

use Gabha\RewardCoins\Services\CoinDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued: opens the post-delivery return window on an order's pending coins.
 *
 * Dispatched after the surrounding transaction commits, then re-checks
 * delivery against the committed order (see {@see CoinDeliveryService}).
 * Idempotent: a window already stamped is never moved, so retries and
 * duplicate webhooks are harmless.
 *
 * Carries only the order id (an int) and the computed unlock timestamp,
 * never the Bagisto order model — see
 * {@see \Gabha\RewardCoins\Listeners\ConfirmCoinsOnDelivery}.
 */
class ConfirmCoinsForOrderDelivery implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public readonly int $orderId,
        public readonly Carbon $availableAt,
    ) {
        $this->onQueue((string) config('reward_coins.queue', 'coins'));
        $this->afterCommit();
    }

    public function handle(CoinDeliveryService $delivery): void
    {
        $delivery->openReturnWindow($this->orderId, $this->availableAt);
    }

    public function failed(Throwable $e): void
    {
        Log::error('RewardCoins: failed to open the coin return window on delivery.', [
            'order_id' => $this->orderId,
            'error'    => $e->getMessage(),
        ]);
    }
}

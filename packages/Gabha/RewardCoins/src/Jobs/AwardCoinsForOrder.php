<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Jobs;

use Gabha\RewardCoins\DTOs\CoinEarningPayload;
use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Services\CoinEarningCalculator;
use Gabha\RewardCoins\Services\CoinOrderReversalService;
use Gabha\RewardCoins\Services\CoinWalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued: credits the coins earned on an order as one pending lot.
 *
 * Idempotent: the lot is keyed `earn:order:{id}`, so a duplicate event, a
 * retry after a worker crash, or two workers racing can only ever credit the
 * order once. Dispatched after the checkout transaction commits.
 *
 * Carries only the already-built, fully-primitive {@see CoinEarningPayload}
 * (never the Bagisto order model) so this job is always safe to serialize
 * onto a real queue — see {@see \Gabha\RewardCoins\Listeners\AwardCoinsOnOrder}
 * for why the order model itself isn't.
 */
class AwardCoinsForOrder implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public readonly CoinEarningPayload $payload,
    ) {
        $this->onQueue((string) config('reward_coins.queue', 'coins'));
        $this->afterCommit();
    }

    /**
     * Idempotency key of an order's earning lot.
     *
     * @param  int  $orderId
     * @return string
     */
    public static function operationKey(int $orderId): string
    {
        return sprintf('earn:order:%d', $orderId);
    }

    public function handle(
        CoinEarningCalculator $calculator,
        CoinWalletService $walletService,
        CoinOrderReversalService $reversal,
    ): void {
        $coins = $calculator->calculate($this->payload);

        if ($coins <= 0) {
            return;
        }

        $walletService->credit(
            customerId: $this->payload->customerId,
            amount: $coins,
            type: TransactionType::Earned,
            orderId: $this->payload->orderId,
            note: sprintf('Earned on order #%s', $this->payload->orderIncrementId),
            status: TransactionStatus::Pending,
            operationKey: self::operationKey($this->payload->orderId),
        );

        // If the order was cancelled/refunded before this (queued) job ran, the
        // reversal found no lot to void; settle it now. A no-op otherwise.
        $reversal->sync($this->payload->orderId, 'award');
    }

    public function failed(Throwable $e): void
    {
        Log::error('RewardCoins: failed to award coins on order.', [
            'order_id' => $this->payload->orderId,
            'error'    => $e->getMessage(),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Jobs;

use Gabha\RewardCoins\DTOs\CoinEarningPayload;
use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Services\CoinEarningCalculator;
use Gabha\RewardCoins\Services\CoinWalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued: credits the coins earned on an order.
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
    }

    public function handle(CoinEarningCalculator $calculator, CoinWalletService $walletService): void
    {
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
        );
    }

    public function failed(Throwable $e): void
    {
        Log::error('RewardCoins: failed to award coins on order.', [
            'order_id' => $this->payload->orderId,
            'error'    => $e->getMessage(),
        ]);
    }
}

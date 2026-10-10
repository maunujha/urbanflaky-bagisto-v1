<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Services;

use Gabha\RewardCoins\Repositories\Contracts\CoinTransactionRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Lapses lots whose expiry has passed.
 *
 * Only each lot's unspent remainder is expired ({@see CoinWalletService::expireLot()}),
 * one lot per transaction, streamed in bounded chunks. A failing lot is logged
 * and skipped so the rest of the run still completes; because each lot is
 * idempotent, re-running after a crash simply picks up what is left.
 */
class CoinExpiryService
{
    public function __construct(
        private readonly CoinTransactionRepositoryInterface $ledger,
        private readonly CoinWalletService $walletService,
    ) {
    }

    /**
     * Expire all due lots.
     *
     * @return array{lots: int, coins: int, failed: int}
     */
    public function expireOldCoins(): array
    {
        $result = ['lots' => 0, 'coins' => 0, 'failed' => 0];

        foreach ($this->ledger->lazyExpiredLots((int) config('reward_coins.batch_size', 200)) as $lot) {
            try {
                $result['coins'] += $this->walletService->expireLot($lot);
                $result['lots']++;
            } catch (Throwable $e) {
                $result['failed']++;

                Log::error('RewardCoins: failed to expire coin lot.', [
                    'transaction_id' => $lot->id,
                    'error'          => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }
}

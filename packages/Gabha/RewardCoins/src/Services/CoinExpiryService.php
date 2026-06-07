<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Services;

use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Repositories\Contracts\CoinTransactionRepositoryInterface;
use Gabha\RewardCoins\Repositories\Contracts\CoinWalletRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Lapses confirmed coins whose expiry window has passed.
 *
 * Each expiry is its own atomic unit (ledger flip + balance debit) so one bad
 * row can never roll back the rest of the run.
 */
class CoinExpiryService
{
    public function __construct(
        private readonly CoinTransactionRepositoryInterface $ledger,
        private readonly CoinWalletRepositoryInterface $wallets,
    ) {
    }

    /**
     * Expire all due coin batches and return how many were processed.
     *
     * @return int
     */
    public function expireOldCoins(): int
    {
        $count = 0;

        foreach ($this->ledger->getExpired() as $transaction) {
            DB::transaction(function () use ($transaction): void {
                $this->wallets->decrementBalance((int) $transaction->customer_id, (int) $transaction->amount);

                $transaction->status = TransactionStatus::Expired;
                $transaction->save();
            });

            $count++;
        }

        return $count;
    }
}

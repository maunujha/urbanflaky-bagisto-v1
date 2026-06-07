<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Console;

use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Repositories\Contracts\CoinTransactionRepositoryInterface;
use Gabha\RewardCoins\Services\CoinWalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Promotes pending earned coins to spendable once their post-delivery return
 * window has elapsed (available_at <= now).
 *
 * Scheduled daily by the service provider; can also be run on demand:
 * `php artisan reward-coins:confirm-available`.
 *
 * Reuses {@see CoinWalletService::confirm()} so each promotion is atomic and
 * idempotent (pending -> balance, status -> confirmed) — no raw wallet SQL.
 */
class ConfirmAvailableCoinsCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'reward-coins:confirm-available';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Confirm pending coins whose post-delivery return window has elapsed.';

    /**
     * Execute the console command.
     *
     * @param  CoinTransactionRepositoryInterface  $transactions
     * @param  CoinWalletService  $walletService
     * @return int
     */
    public function handle(
        CoinTransactionRepositoryInterface $transactions,
        CoinWalletService $walletService,
    ): int {
        if (! CoinSetting::isEnabled()) {
            $this->info('RewardCoins is disabled. Skipping.');

            return self::SUCCESS;
        }

        $due = $transactions->getAvailableForConfirmation();

        if ($due->isEmpty()) {
            $this->info('No coins ready to confirm.');

            return self::SUCCESS;
        }

        $confirmed = 0;
        $coins = 0;

        foreach ($due as $transaction) {
            $walletService->confirm($transaction);

            $confirmed++;
            $coins += (int) $transaction->amount;

            Log::info(sprintf(
                'RewardCoins: confirmed %d coins for customer #%d (txn #%d).',
                (int) $transaction->amount,
                (int) $transaction->customer_id,
                (int) $transaction->id,
            ));
        }

        $this->info(sprintf('Confirmed %d coin(s) across %d transaction(s).', $coins, $confirmed));

        return self::SUCCESS;
    }
}

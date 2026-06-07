<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Console;

use Gabha\RewardCoins\Services\CoinExpiryService;
use Illuminate\Console\Command;

/**
 * Expires coins whose window has lapsed. Scheduled daily by the service
 * provider; can also be run on demand: `php artisan reward-coins:expire`.
 */
class ExpireCoinsCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'reward-coins:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire reward coins whose expiry date has passed and debit the wallets.';

    /**
     * Execute the console command.
     *
     * @param  CoinExpiryService  $expiryService
     * @return int
     */
    public function handle(CoinExpiryService $expiryService): int
    {
        $count = $expiryService->expireOldCoins();

        $this->info(sprintf('Expired %d coin transaction(s).', $count));

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Console;

use Gabha\RewardCoins\Services\CoinExpiryService;
use Illuminate\Console\Command;

/**
 * Expires the unspent remainder of lapsed coin lots. Scheduled daily by the
 * service provider; can also be run on demand: `php artisan reward-coins:expire`.
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
    protected $description = 'Expire the unspent remainder of reward-coin lots whose expiry date has passed.';

    /**
     * Execute the console command.
     *
     * @param  CoinExpiryService  $expiryService
     * @return int
     */
    public function handle(CoinExpiryService $expiryService): int
    {
        $result = $expiryService->expireOldCoins();

        $this->info(sprintf(
            'Expired %d coin(s) across %d lot(s); %d lot(s) failed.',
            $result['coins'],
            $result['lots'],
            $result['failed'],
        ));

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}

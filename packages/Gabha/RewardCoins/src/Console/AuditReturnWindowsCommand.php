<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Console;

use Gabha\RewardCoins\Services\CoinDeliveryService;
use Illuminate\Console\Command;

/**
 * Dry-run report of return windows opened without valid delivery evidence
 * (e.g. at shipment creation, before the delivery rule was fixed), with a
 * proposed correction per lot. READ-ONLY: it never changes a date or balance.
 *
 * `php artisan reward-coins:audit-windows`
 */
class AuditReturnWindowsCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'reward-coins:audit-windows';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Report (never fix) coin return windows opened before genuine delivery.';

    /**
     * Execute the console command.
     *
     * @param  CoinDeliveryService  $delivery
     * @return int
     */
    public function handle(CoinDeliveryService $delivery): int
    {
        $rows = $delivery->auditWindows((int) config('reward_coins.batch_size', 200));

        if ($rows === []) {
            $this->info('No return windows need correction.');

            return self::SUCCESS;
        }

        $this->warn(sprintf('%d return window(s) lack a valid delivery basis. Nothing was changed.', count($rows)));

        $this->table(['order', 'lot', 'coins', 'lot_status', 'current_available', 'basis', 'proposed_action', 'proposed_available'], $rows);

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Console;

use Gabha\RewardCoins\Services\CoinLotBackfillService;
use Illuminate\Console\Command;

/**
 * Reconstruct `remaining` on confirmed lots written before lot accounting.
 *
 * DRY RUN BY DEFAULT: prints what would happen per customer. `--apply` writes
 * lot remainders and allocations for customers whose replay matches their
 * wallet exactly; wallet balances are never changed. Customers that cannot be
 * reconstructed are skipped and listed with the reason.
 *
 * `php artisan reward-coins:backfill-lots [--customer=ID] [--apply]`
 */
class BackfillLotsCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'reward-coins:backfill-lots
        {--customer= : Only this customer id}
        {--apply : Write the accepted plans (default is a dry run)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconstruct per-lot remainders for coins earned before lot accounting (dry run unless --apply).';

    /**
     * Execute the console command.
     *
     * @param  CoinLotBackfillService  $backfill
     * @return int
     */
    public function handle(CoinLotBackfillService $backfill): int
    {
        $apply      = (bool) $this->option('apply');
        $customerId = $this->option('customer') !== null ? (int) $this->option('customer') : null;

        $rows = [];

        foreach ($backfill->candidates($customerId) as $candidate) {
            $rows[] = $backfill->backfill($candidate, $apply);
        }

        if ($rows === []) {
            $this->info('No legacy lots need backfilling.');

            return self::SUCCESS;
        }

        $this->table(['customer_id', 'result', 'reason', 'lots', 'coins'], $rows);

        $this->line($apply ? 'Applied.' : 'Dry run: nothing was written. Re-run with --apply to write accepted plans.');

        return self::SUCCESS;
    }
}

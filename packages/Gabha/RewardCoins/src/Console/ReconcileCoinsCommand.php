<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Console;

use Gabha\RewardCoins\Services\CoinReconciliationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Read-only audit of wallets against their lots, allocations and ledger.
 *
 * Reports every discrepancy with expected vs actual values and exits non-zero
 * when any is found. Never writes: repairing a wallet is a separate, approved
 * procedure. `php artisan reward-coins:reconcile [--customer=ID]`.
 */
class ReconcileCoinsCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'reward-coins:reconcile
        {--customer= : Audit a single customer id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect (never fix) mismatches between coin wallets, lots, allocations and the ledger.';

    /**
     * Execute the console command.
     *
     * @param  CoinReconciliationService  $reconciliation
     * @return int
     */
    public function handle(CoinReconciliationService $reconciliation): int
    {
        $customerId = $this->option('customer') !== null ? (int) $this->option('customer') : null;

        $report = $reconciliation->run($customerId, (int) config('reward_coins.batch_size', 200));

        if ($report['legacy'] !== []) {
            $this->warn(sprintf('%d wallet(s) hold balance that predates lot accounting (not an error; see reward-coins:backfill-lots):', count($report['legacy'])));

            $this->table(['customer_id', 'legacy_coins', 'unbackfilled_lots'], $report['legacy']);
        }

        if ($report['mismatches'] === []) {
            $this->info(sprintf('Audited %d wallet(s): no mismatches.', $report['wallets']));

            return self::SUCCESS;
        }

        $this->error(sprintf('Audited %d wallet(s): %d mismatch(es).', $report['wallets'], count($report['mismatches'])));

        $this->table(['check', 'customer_id', 'subject', 'expected', 'actual'], $report['mismatches']);

        Log::warning('RewardCoins: reconciliation found mismatches.', [
            'count'      => count($report['mismatches']),
            'mismatches' => array_slice($report['mismatches'], 0, 50),
        ]);

        return self::FAILURE;
    }
}

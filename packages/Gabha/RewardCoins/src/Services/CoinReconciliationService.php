<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Services;

use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Models\CustomerCoinWallet;
use Gabha\RewardCoins\Repositories\CoinTransactionRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only consistency audit (see docs/REWARD-COINS-ACCOUNTING.md §Invariants).
 *
 * Per wallet, in bounded chunks and with one aggregate query per check:
 *  I1  pending_balance == Σ amount of pending earned lots
 *  I2  balance >= Σ remaining of confirmed lots (lots can never claim more
 *      than the wallet holds); and == when the customer has no history from
 *      before lot accounting
 *  I3  each tracked lot: remaining == amount − Σ allocations drawn from it
 *  I4  each keyed debit: amount == Σ its allocations
 *  I5  each allocation: restored <= amount
 *
 * Never writes. For customers with history from before lot accounting, the
 * part of the balance no lot accounts for is reported as "legacy" rather
 * than as an error.
 */
class CoinReconciliationService
{
    /**
     * Debit ledger types.
     *
     * @var array<int, string>
     */
    private const DEBIT_TYPES = [
        'redeemed',
        'revoked',
        'expired',
        'deducted',
    ];

    /**
     * Audit every wallet (or one customer's).
     *
     * @param  int|null  $customerId
     * @param  int  $chunkSize
     * @return array{wallets: int, mismatches: array<int, array<string, mixed>>, legacy: array<int, array<string, mixed>>}
     */
    public function run(?int $customerId = null, int $chunkSize = 200): array
    {
        $report = ['wallets' => 0, 'mismatches' => [], 'legacy' => []];

        CustomerCoinWallet::query()
            ->when($customerId !== null, fn ($q) => $q->where('customer_id', $customerId))
            ->orderBy('id')
            ->chunkById($chunkSize, function (Collection $wallets) use (&$report): void {
                $report['wallets'] += $wallets->count();

                $this->auditChunk($wallets, $report);
            });

        return $report;
    }

    /**
     * @param  Collection<int, CustomerCoinWallet>  $wallets
     * @param  array<string, mixed>  $report
     * @return void
     */
    private function auditChunk(Collection $wallets, array &$report): void
    {
        $ids = $wallets->pluck('customer_id')->all();

        $pending = DB::table('coin_transactions')
            ->whereIn('customer_id', $ids)
            ->where('type', TransactionType::Earned->value)
            ->where('status', TransactionStatus::Pending->value)
            ->groupBy('customer_id')
            ->pluck(DB::raw('SUM(amount)'), 'customer_id');

        $lots = DB::table('coin_transactions')
            ->whereIn('customer_id', $ids)
            ->whereIn('type', CoinTransactionRepository::LOT_TYPES)
            ->where('status', TransactionStatus::Confirmed->value)
            ->whereNotNull('remaining')
            ->groupBy('customer_id')
            ->pluck(DB::raw('SUM(remaining)'), 'customer_id');

        // Rows written before lot accounting carry no operation key.
        $legacyRows = DB::table('coin_transactions')
            ->whereIn('customer_id', $ids)
            ->whereNull('operation_key')
            ->groupBy('customer_id')
            ->pluck(DB::raw('COUNT(*)'), 'customer_id');

        $unbackfilled = DB::table('coin_transactions')
            ->whereIn('customer_id', $ids)
            ->whereIn('type', CoinTransactionRepository::LOT_TYPES)
            ->where('status', TransactionStatus::Confirmed->value)
            ->whereNull('remaining')
            ->groupBy('customer_id')
            ->pluck(DB::raw('COUNT(*)'), 'customer_id');

        foreach ($wallets as $wallet) {
            $cid      = (int) $wallet->customer_id;
            $expPend  = (int) ($pending[$cid] ?? 0);
            $lotTotal = (int) ($lots[$cid] ?? 0);
            $balance  = (int) $wallet->balance;

            if ((int) $wallet->pending_balance !== $expPend) {
                $report['mismatches'][] = $this->row('I1 pending_balance', $cid, 'wallet', $expPend, (int) $wallet->pending_balance);
            }

            if ($lotTotal > $balance) {
                $report['mismatches'][] = $this->row('I2 balance >= lots', $cid, 'wallet', '>= '.$lotTotal, $balance);
            } elseif ((int) ($legacyRows[$cid] ?? 0) === 0 && $lotTotal !== $balance) {
                $report['mismatches'][] = $this->row('I2 balance == lots', $cid, 'wallet', $lotTotal, $balance);
            } elseif ($lotTotal !== $balance) {
                $report['legacy'][] = [
                    'customer_id'       => $cid,
                    'legacy_coins'      => $balance - $lotTotal,
                    'unbackfilled_lots' => (int) ($unbackfilled[$cid] ?? 0),
                ];
            }
        }

        $lotRows = DB::table('coin_transactions as t')
            ->leftJoin('coin_allocations as a', 'a.credit_transaction_id', '=', 't.id')
            ->whereIn('t.customer_id', $ids)
            ->whereNotNull('t.remaining')
            ->groupBy('t.id', 't.customer_id', 't.amount', 't.remaining')
            ->havingRaw('t.remaining <> t.amount - COALESCE(SUM(a.amount), 0)')
            ->get(['t.id', 't.customer_id', 't.amount', 't.remaining', DB::raw('COALESCE(SUM(a.amount), 0) as drawn')]);

        foreach ($lotRows as $lot) {
            $report['mismatches'][] = $this->row('I3 lot remaining', (int) $lot->customer_id, 'txn #'.$lot->id, (int) $lot->amount - (int) $lot->drawn, (int) $lot->remaining);
        }

        $debitRows = DB::table('coin_transactions as t')
            ->leftJoin('coin_allocations as a', 'a.debit_transaction_id', '=', 't.id')
            ->whereIn('t.customer_id', $ids)
            ->whereIn('t.type', self::DEBIT_TYPES)
            ->whereNotNull('t.operation_key')
            ->groupBy('t.id', 't.customer_id', 't.amount')
            ->havingRaw('t.amount <> COALESCE(SUM(a.amount), 0)')
            ->get(['t.id', 't.customer_id', 't.amount', DB::raw('COALESCE(SUM(a.amount), 0) as allocated')]);

        foreach ($debitRows as $debit) {
            $report['mismatches'][] = $this->row('I4 debit allocated', (int) $debit->customer_id, 'txn #'.$debit->id, (int) $debit->amount, (int) $debit->allocated);
        }

        $overRestored = DB::table('coin_allocations as a')
            ->join('coin_transactions as t', 't.id', '=', 'a.debit_transaction_id')
            ->whereIn('t.customer_id', $ids)
            ->whereColumn('a.restored', '>', 'a.amount')
            ->get(['a.id', 't.customer_id', 'a.amount', 'a.restored']);

        foreach ($overRestored as $allocation) {
            $report['mismatches'][] = $this->row('I5 allocation restored', (int) $allocation->customer_id, 'alloc #'.$allocation->id, '<= '.$allocation->amount, (int) $allocation->restored);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function row(string $check, int $customerId, string $subject, int|string $expected, int|string $actual): array
    {
        return [
            'check'       => $check,
            'customer_id' => $customerId,
            'subject'     => $subject,
            'expected'    => $expected,
            'actual'      => $actual,
        ];
    }
}

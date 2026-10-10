<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Services;

use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Models\CoinAllocation;
use Gabha\RewardCoins\Models\CoinTransaction;
use Gabha\RewardCoins\Repositories\CoinTransactionRepository;
use Gabha\RewardCoins\Repositories\Contracts\CoinWalletRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reconstructs `remaining` for confirmed lots written before lot accounting.
 *
 * Replays the customer's legacy debits against their legacy lots in the same
 * spend order the live system now uses (soonest-expiring first, only lots that
 * existed at the time of each debit). The result is accepted ONLY if it lands
 * exactly on the current wallet balance; otherwise the customer is reported
 * and left untouched. Customers whose history is ambiguous are never guessed:
 *  - legacy `adjusted` rows (direction was not recorded);
 *  - earned lots already `expired` by the old job (it debited the full amount,
 *    whatever had been spent).
 *
 * Wallet balances are never changed; only `remaining` and allocation rows are
 * written, and only with $apply = true.
 */
class CoinLotBackfillService
{
    public function __construct(
        private readonly CoinWalletRepositoryInterface $wallets,
    ) {
    }

    /**
     * Customers holding confirmed lots with no tracked remainder.
     *
     * @return Collection<int, int>
     */
    public function candidates(?int $customerId = null): Collection
    {
        return CoinTransaction::query()
            ->whereIn('type', CoinTransactionRepository::LOT_TYPES)
            ->where('status', TransactionStatus::Confirmed->value)
            ->whereNull('remaining')
            ->when($customerId !== null, fn ($q) => $q->where('customer_id', $customerId))
            ->distinct()
            ->orderBy('customer_id')
            ->pluck('customer_id')
            ->map(fn ($id): int => (int) $id);
    }

    /**
     * Plan (and optionally apply) the backfill for one customer.
     *
     * @param  int  $customerId
     * @param  bool  $apply
     * @return array{customer_id: int, result: string, reason: string, lots: int, coins: int}
     */
    public function backfill(int $customerId, bool $apply): array
    {
        return DB::transaction(function () use ($customerId, $apply): array {
            $wallet = $this->wallets->lock($customerId);

            $rows = CoinTransaction::query()
                ->where('customer_id', $customerId)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $plan = $this->plan($rows, (int) $wallet->balance);

            $summary = [
                'customer_id' => $customerId,
                'result'      => $plan['ok'] ? ($apply ? 'applied' : 'would apply') : 'skipped',
                'reason'      => $plan['reason'],
                'lots'        => count($plan['remaining']),
                'coins'       => array_sum($plan['remaining']),
            ];

            if ($plan['ok'] && $apply) {
                $this->write($plan);
            }

            return $summary;
        });
    }

    /**
     * Pure replay; no writes.
     *
     * @param  Collection<int, CoinTransaction>  $rows
     * @param  int  $balance
     * @return array{ok: bool, reason: string, remaining: array<int, int>, allocations: array<int, array{0: int, 1: int, 2: int}>, legacyDraws: array<int, int>}
     */
    private function plan(Collection $rows, int $balance): array
    {
        $fail = fn (string $reason): array => ['ok' => false, 'reason' => $reason, 'remaining' => [], 'allocations' => [], 'legacyDraws' => []];

        foreach ($rows as $row) {
            if ($row->type === TransactionType::Adjusted && $row->operation_key === null) {
                return $fail(sprintf('legacy adjusted row #%d has no recorded direction', $row->id));
            }

            if ($row->type === TransactionType::Earned && $row->status === TransactionStatus::Expired && $row->remaining === null) {
                return $fail(sprintf('lot #%d was expired by the pre-fix job (full-amount debit)', $row->id));
            }
        }

        $lots = $rows->filter(fn (CoinTransaction $r): bool => in_array($r->type->value, CoinTransactionRepository::LOT_TYPES, true)
            && $r->status === TransactionStatus::Confirmed
            && $r->remaining === null);

        $remaining = $lots->mapWithKeys(fn (CoinTransaction $r): array => [(int) $r->id => (int) $r->amount])->all();

        $cancelledOrders = $rows
            ->filter(fn (CoinTransaction $r): bool => $r->type === TransactionType::Earned && $r->status === TransactionStatus::Cancelled)
            ->pluck('order_id')
            ->filter()
            ->all();

        // Legacy debits: redeemed (not reversed in place) and balance claw-backs.
        $debits = [];

        foreach ($rows as $row) {
            if ($row->operation_key !== null) {
                continue;
            }

            if ($row->type === TransactionType::Redeemed && $row->status === TransactionStatus::Confirmed) {
                $debits[] = ['debit' => (int) $row->id, 'amount' => (int) $row->amount, 'legacy_alloc' => null];
            }

            if ($row->type === TransactionType::Revoked && ! in_array($row->order_id, $cancelledOrders, false)) {
                $debits[] = ['debit' => (int) $row->id, 'amount' => (int) $row->amount, 'legacy_alloc' => null];
            }
        }

        // Post-fix debits that drew on legacy balance (allocation with no lot).
        $legacyAllocations = CoinAllocation::query()
            ->whereNull('credit_transaction_id')
            ->whereIn('debit_transaction_id', $rows->pluck('id'))
            ->orderBy('id')
            ->get();

        foreach ($legacyAllocations as $allocation) {
            if ((int) $allocation->restored > 0) {
                return $fail(sprintf('legacy allocation #%d was partly restored', $allocation->id));
            }

            $debits[] = ['debit' => (int) $allocation->debit_transaction_id, 'amount' => (int) $allocation->amount, 'legacy_alloc' => (int) $allocation->id];
        }

        usort($debits, fn (array $a, array $b): int => [$a['debit'], $a['legacy_alloc'] ?? 0] <=> [$b['debit'], $b['legacy_alloc'] ?? 0]);

        $order = $lots->sortBy([
            fn ($a, $b) => ($a->expires_at === null) <=> ($b->expires_at === null),
            fn ($a, $b) => $a->expires_at <=> $b->expires_at,
            fn ($a, $b) => $a->id <=> $b->id,
        ]);

        $allocations = [];
        $legacyDraws = [];

        foreach ($debits as $debit) {
            $left = $debit['amount'];

            foreach ($order as $lot) {
                if ($left <= 0) {
                    break;
                }

                // Only lots that existed when the debit happened.
                if ((int) $lot->id >= $debit['debit'] || $remaining[(int) $lot->id] <= 0) {
                    continue;
                }

                $take = min($left, $remaining[(int) $lot->id]);

                $remaining[(int) $lot->id] -= $take;
                $allocations[]               = [$debit['debit'], (int) $lot->id, $take];
                $left                       -= $take;
            }

            if ($left > 0) {
                return $fail(sprintf('debit #%d exceeds the lots that existed before it', $debit['debit']));
            }

            if ($debit['legacy_alloc'] !== null) {
                $legacyDraws[] = $debit['legacy_alloc'];
            }
        }

        $tracked = (int) $rows
            ->filter(fn (CoinTransaction $r): bool => in_array($r->type->value, CoinTransactionRepository::LOT_TYPES, true)
                && $r->status === TransactionStatus::Confirmed
                && $r->remaining !== null)
            ->sum('remaining');

        if (array_sum($remaining) + $tracked !== $balance) {
            return $fail(sprintf('replay ends at %d coins but the wallet holds %d', array_sum($remaining) + $tracked, $balance));
        }

        return ['ok' => true, 'reason' => 'replay matches wallet balance', 'remaining' => $remaining, 'allocations' => $allocations, 'legacyDraws' => $legacyDraws];
    }

    /**
     * Persist an accepted plan.
     *
     * @param  array<string, mixed>  $plan
     * @return void
     */
    private function write(array $plan): void
    {
        foreach ($plan['remaining'] as $lotId => $remaining) {
            CoinTransaction::query()->whereKey($lotId)->update(['remaining' => $remaining]);
        }

        // Post-fix legacy draws are replaced by their lot-specific split.
        CoinAllocation::query()->whereKey($plan['legacyDraws'])->delete();

        $now = now();

        foreach (array_chunk($plan['allocations'], 500) as $chunk) {
            CoinAllocation::query()->insert(array_map(fn (array $a): array => [
                'debit_transaction_id'  => $a[0],
                'credit_transaction_id' => $a[1],
                'amount'                => $a[2],
                'restored'              => 0,
                'created_at'            => $now,
                'updated_at'            => $now,
            ], $chunk));
        }
    }
}

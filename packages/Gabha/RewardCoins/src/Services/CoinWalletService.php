<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Services;

use Closure;
use DateTimeInterface;
use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Exceptions\InsufficientCoinsException;
use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Models\CoinTransaction;
use Gabha\RewardCoins\Models\CustomerCoinWallet;
use Gabha\RewardCoins\Repositories\Contracts\CoinTransactionRepositoryInterface;
use Gabha\RewardCoins\Repositories\Contracts\CoinWalletRepositoryInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The single writer of coin movements.
 *
 * Accounting model (see docs/REWARD-COINS-ACCOUNTING.md):
 *  - Every credit (earned, restored, admin-added) opens a *lot* whose
 *    `remaining` tracks its unspent coins.
 *  - Every debit (redeemed, revoked, expired, admin-deducted) consumes lots
 *    soonest-expiring first (non-expiring last, then oldest first) and records
 *    which lot it drew on in `coin_allocations`. Balance that predates lot
 *    accounting and was never backfilled is drawn last as "legacy" balance.
 *
 * Each public method runs in one DB transaction that first locks the
 * customer's wallet row, so movements for one customer serialise, lot reads
 * are current, and the ledger row, lot updates and wallet update commit or
 * roll back together. Methods given an operation key are idempotent: a repeat
 * returns the original row and changes nothing, enforced by a UNIQUE index.
 */
class CoinWalletService
{
    /**
     * Attempts for a top-level coin transaction. Laravel replays it only on a
     * deadlock / lock-wait timeout, and only when it is not nested inside an
     * outer transaction; idempotency keys make the replay safe.
     */
    private const ATTEMPTS = 3;

    public function __construct(
        private readonly CoinWalletRepositoryInterface $wallets,
        private readonly CoinTransactionRepositoryInterface $ledger,
    ) {
    }

    /**
     * Lock the customer's wallet for the rest of the current transaction.
     *
     * Callers composing several movements into one unit (order reversals) take
     * this first so their reads and the movements they post cannot interleave
     * with another worker's.
     *
     * @param  int  $customerId
     * @return CustomerCoinWallet
     */
    public function lockWallet(int $customerId): CustomerCoinWallet
    {
        return $this->wallets->lock($customerId);
    }

    /**
     * Add coins to a customer as a new lot (pending or confirmed).
     *
     * @param  int  $customerId
     * @param  int  $amount  Positive coin count.
     * @param  TransactionType  $type  Earned, Adjusted or Refunded.
     * @param  int|null  $orderId
     * @param  string  $note
     * @param  TransactionStatus  $status  Pending parks coins; Confirmed makes them spendable.
     * @param  string|null  $operationKey  Idempotency key for this logical credit.
     * @param  array<string, mixed>  $meta
     * @return CoinTransaction
     *
     * @throws InvalidArgumentException
     */
    public function credit(
        int $customerId,
        int $amount,
        TransactionType $type,
        ?int $orderId = null,
        string $note = '',
        TransactionStatus $status = TransactionStatus::Confirmed,
        ?string $operationKey = null,
        array $meta = [],
    ): CoinTransaction {
        $this->guardPositive($amount);

        return $this->once($customerId, $operationKey, function () use ($customerId, $amount, $type, $orderId, $note, $status, $operationKey, $meta): CoinTransaction {
            $lot = $this->ledger->record(
                customerId: $customerId,
                type: $type,
                status: $status,
                amount: $amount,
                orderId: $orderId,
                note: $note !== '' ? $note : null,
                expiresAt: $this->expiryFor($type),
                remaining: $amount,
                operationKey: $operationKey,
                meta: $meta,
            );

            if ($status === TransactionStatus::Pending) {
                $this->wallets->incrementPending($customerId, $amount);
            } else {
                $this->wallets->incrementBalance($customerId, $amount);
            }

            return $lot;
        });
    }

    /**
     * Remove coins from a customer's spendable balance, drawing on lots in
     * spend order.
     *
     * @param  int  $customerId
     * @param  int  $amount  Positive coin count.
     * @param  TransactionType  $type  Redeemed or Deducted.
     * @param  int|null  $orderId
     * @param  string  $note
     * @param  string|null  $operationKey
     * @param  array<string, mixed>  $meta
     * @return CoinTransaction
     *
     * @throws InvalidArgumentException
     * @throws InsufficientCoinsException
     */
    public function debit(
        int $customerId,
        int $amount,
        TransactionType $type,
        ?int $orderId = null,
        string $note = '',
        ?string $operationKey = null,
        array $meta = [],
    ): CoinTransaction {
        $this->guardPositive($amount);

        return $this->once($customerId, $operationKey, function (CustomerCoinWallet $wallet) use ($customerId, $amount, $type, $orderId, $note, $operationKey, $meta): CoinTransaction {
            if ((int) $wallet->balance < $amount) {
                throw InsufficientCoinsException::for($amount, (int) $wallet->balance);
            }

            $debit = $this->ledger->record(
                customerId: $customerId,
                type: $type,
                status: TransactionStatus::Confirmed,
                amount: $amount,
                orderId: $orderId,
                note: $note !== '' ? $note : null,
                operationKey: $operationKey,
                meta: $meta,
            );

            $this->consume($wallet, $debit, $amount);

            if ($type === TransactionType::Redeemed) {
                $this->wallets->applyRedemption($customerId, $amount);
            } else {
                $this->wallets->decrementBalance($customerId, $amount);
            }

            return $debit;
        });
    }

    /**
     * Promote a pending earned lot to confirmed (spendable).
     *
     * Idempotent: a lot that is no longer pending is returned untouched.
     *
     * @param  CoinTransaction  $lot
     * @return CoinTransaction
     */
    public function confirm(CoinTransaction $lot): CoinTransaction
    {
        return DB::transaction(function () use ($lot): CoinTransaction {
            $this->wallets->lock((int) $lot->customer_id);

            $lot = $this->ledger->lockById((int) $lot->id) ?? $lot;

            if ($lot->status !== TransactionStatus::Pending) {
                return $lot;
            }

            $amount = $lot->remaining ?? (int) $lot->amount;

            $lot->status    = TransactionStatus::Confirmed;
            $lot->remaining = $amount;
            $lot->save();

            $this->wallets->confirmPending((int) $lot->customer_id, $amount);

            return $lot;
        });
    }

    /**
     * Confirm every pending lot a customer is holding (manual admin approval).
     *
     * @param  int  $customerId
     * @return int  Coins confirmed.
     */
    public function confirmAllForCustomer(int $customerId): int
    {
        return DB::transaction(function () use ($customerId): int {
            $confirmed = 0;

            foreach ($this->ledger->getPendingForCustomer($customerId) as $lot) {
                $before = $lot->status;

                $lot = $this->confirm($lot);

                if ($before === TransactionStatus::Pending && $lot->status === TransactionStatus::Confirmed) {
                    $confirmed += (int) $lot->remaining;
                }
            }

            return $confirmed;
        });
    }

    /**
     * Expire whatever is left of a lapsed lot.
     *
     * Only the unspent remainder leaves the wallet; coins already spent,
     * revoked or expired from this lot are never taken a second time.
     * Idempotent per lot.
     *
     * @param  CoinTransaction  $lot
     * @return int  Coins expired.
     */
    public function expireLot(CoinTransaction $lot): int
    {
        return DB::transaction(function () use ($lot): int {
            $this->wallets->lock((int) $lot->customer_id);

            $lot = $this->ledger->lockById((int) $lot->id);

            if (
                ! $lot
                || $lot->status !== TransactionStatus::Confirmed
                || $lot->remaining === null
                || ! $lot->expires_at
                || $lot->expires_at->isFuture()
            ) {
                return 0;
            }

            return $this->expireRemainder($lot);
        });
    }

    /**
     * Expire a locked lot's remainder: one `expired` row for exactly what is
     * left, drawn from this lot only. Caller holds the wallet lock.
     *
     * @param  CoinTransaction  $lot
     * @return int  Coins expired.
     */
    private function expireRemainder(CoinTransaction $lot): int
    {
        $expired = (int) $lot->remaining;

        if ($expired > 0) {
            $row = $this->ledger->record(
                customerId: (int) $lot->customer_id,
                type: TransactionType::Expired,
                status: TransactionStatus::Confirmed,
                amount: $expired,
                orderId: $lot->order_id ? (int) $lot->order_id : null,
                note: sprintf('Expired %d unspent coin(s) from transaction #%d.', $expired, (int) $lot->id),
                operationKey: sprintf('expire:lot:%d', (int) $lot->id),
                meta: ['lot_id' => (int) $lot->id],
            );

            $this->ledger->allocate($row, $lot, $expired);

            $this->wallets->decrementBalance((int) $lot->customer_id, $expired);
        }

        $lot->remaining = 0;
        $lot->status    = TransactionStatus::Expired;
        $lot->save();

        return $expired;
    }

    /**
     * Void a still-pending earned lot (order cancelled or refunded before the
     * coins became spendable). Records a `revoked` row for the trail.
     *
     * @param  CoinTransaction  $lot
     * @param  string  $operationKey
     * @param  string  $note
     * @param  array<string, mixed>  $meta
     * @return int  Coins voided (0 when the lot was no longer pending).
     */
    public function cancelPendingLot(CoinTransaction $lot, string $operationKey, string $note, array $meta = []): int
    {
        $voided = 0;

        $this->once((int) $lot->customer_id, $operationKey, function () use ($lot, $operationKey, $note, $meta, &$voided): ?CoinTransaction {
            $lot = $this->ledger->lockById((int) $lot->id);

            if (! $lot || $lot->status !== TransactionStatus::Pending) {
                return null;
            }

            $voided = $lot->remaining ?? (int) $lot->amount;

            $row = $this->ledger->record(
                customerId: (int) $lot->customer_id,
                type: TransactionType::Revoked,
                status: TransactionStatus::Confirmed,
                amount: $voided,
                orderId: $lot->order_id ? (int) $lot->order_id : null,
                note: $note,
                operationKey: $operationKey,
                meta: $meta + ['lot_id' => (int) $lot->id, 'from_pending' => true],
            );

            if ($voided > 0) {
                $this->ledger->allocate($row, $lot, $voided);
            }

            $lot->remaining = 0;
            $lot->status    = TransactionStatus::Cancelled;
            $lot->save();

            $this->wallets->decrementPending((int) $lot->customer_id, $voided);

            return $row;
        });

        return $voided;
    }

    /**
     * Claw back confirmed earned coins, drawing on the earning lot first and
     * then the rest of the balance. The wallet never goes negative: whatever
     * cannot be recovered (already spent) is recorded as `shortfall` on the
     * row's meta and is never chased later.
     *
     * @param  CoinTransaction  $lot  The order's earned lot.
     * @param  int  $amount  Coins owed back.
     * @param  string  $operationKey
     * @param  string  $note
     * @param  array<string, mixed>  $meta
     * @return CoinTransaction
     */
    public function revokeEarned(CoinTransaction $lot, int $amount, string $operationKey, string $note, array $meta = []): CoinTransaction
    {
        $this->guardPositive($amount);

        $customerId = (int) $lot->customer_id;

        return $this->once($customerId, $operationKey, function (CustomerCoinWallet $wallet) use ($lot, $customerId, $amount, $operationKey, $note, $meta): CoinTransaction {
            $recovered = min($amount, max(0, (int) $wallet->balance));

            $row = $this->ledger->record(
                customerId: $customerId,
                type: TransactionType::Revoked,
                status: TransactionStatus::Confirmed,
                amount: $recovered,
                orderId: $lot->order_id ? (int) $lot->order_id : null,
                note: $note,
                operationKey: $operationKey,
                meta: $meta + ['lot_id' => (int) $lot->id, 'owed' => $amount, 'shortfall' => $amount - $recovered],
            );

            if ($recovered > 0) {
                $this->consume($wallet, $row, $recovered, (int) $lot->id);

                $this->wallets->decrementBalance($customerId, $recovered);
            }

            return $row;
        });
    }

    /**
     * Hand back redeemed coins. Each restored portion becomes a new `refunded`
     * lot that inherits the expiry of the lot it was originally spent from, so
     * a restore never extends a coin's life. Restores newest allocations first
     * and never exceeds what the redemption actually consumed.
     *
     * @param  CoinTransaction  $redemption  The order's `redeemed` row.
     * @param  int  $amount  Coins to restore now.
     * @param  string  $operationKeyPrefix  Unique per restore step.
     * @param  string  $note
     * @param  array<string, mixed>  $meta
     * @return int  Coins restored.
     */
    public function restoreRedemption(CoinTransaction $redemption, int $amount, string $operationKeyPrefix, string $note, array $meta = []): int
    {
        $this->guardPositive($amount);

        $customerId = (int) $redemption->customer_id;

        return DB::transaction(function () use ($redemption, $customerId, $amount, $operationKeyPrefix, $note, $meta): int {
            $this->wallets->lock($customerId);

            $left        = $amount;
            $allocations = $this->ledger->lockAllocationsFor((int) $redemption->id);

            $portions = [];

            foreach ($allocations as $allocation) {
                $open = (int) $allocation->amount - (int) $allocation->restored;

                if ($open <= 0 || $left <= 0) {
                    continue;
                }

                $take    = min($open, $left);
                $left   -= $take;

                $portions[] = [$allocation, $take, $allocation->lot?->expires_at, sprintf('%s:alloc:%d', $operationKeyPrefix, (int) $allocation->id)];
            }

            // A redemption from before lot accounting has no allocations: restore
            // it as one non-expiring lot (the caller caps the cumulative amount).
            if ($allocations->isEmpty() && $left > 0) {
                $portions[] = [null, $left, null, $operationKeyPrefix.':legacy'];
                $left       = 0;
            }

            $restored = 0;

            foreach ($portions as [$allocation, $take, $expiresAt, $key]) {
                if ($this->ledger->findByOperationKey($key)) {
                    continue;
                }

                $lot = $this->ledger->record(
                    customerId: $customerId,
                    type: TransactionType::Refunded,
                    status: TransactionStatus::Confirmed,
                    amount: $take,
                    orderId: $redemption->order_id ? (int) $redemption->order_id : null,
                    note: $note,
                    expiresAt: $expiresAt instanceof DateTimeInterface ? $expiresAt : null,
                    remaining: $take,
                    operationKey: $key,
                    meta: $meta + [
                        'redemption_id' => (int) $redemption->id,
                        'source_lot_id' => $allocation?->credit_transaction_id,
                    ],
                );

                if ($allocation) {
                    $allocation->restored = (int) $allocation->restored + $take;
                    $allocation->save();
                }

                $this->wallets->revertRedemption($customerId, $take);

                // Coins whose original lot has already lapsed come back expired:
                // they are never spendable, not even until the nightly sweep.
                if ($lot->expires_at && ! $lot->expires_at->isFuture()) {
                    $this->expireRemainder($lot);
                }

                $restored += $take;
            }

            return $restored;
        });
    }

    /**
     * Run a movement under the wallet lock, at most once per operation key.
     *
     * The key is checked with a locking read after the wallet lock is held, so
     * two workers cannot both pass the check; the UNIQUE index on
     * operation_key is the backstop for anything that bypasses this service.
     * Only a duplicate of *this* key is treated as "already done" — any other
     * integrity error is re-thrown.
     *
     * @template T
     *
     * @param  int  $customerId
     * @param  string|null  $operationKey
     * @param  Closure(CustomerCoinWallet): T  $work
     * @return T
     */
    private function once(int $customerId, ?string $operationKey, Closure $work): mixed
    {
        $run = function () use ($customerId, $operationKey, $work): mixed {
            $wallet = $this->wallets->lock($customerId);

            if ($operationKey !== null && ($existing = $this->ledger->findByOperationKey($operationKey))) {
                return $existing;
            }

            return $work($wallet);
        };

        if ($operationKey === null) {
            return DB::transaction($run, self::ATTEMPTS);
        }

        try {
            return DB::transaction($run, self::ATTEMPTS);
        } catch (UniqueConstraintViolationException $e) {
            $existing = DB::transaction(fn () => $this->ledger->findByOperationKey($operationKey));

            if (! $existing) {
                throw $e;
            }

            return $existing;
        }
    }

    /**
     * Draw $amount coins for $debit from the customer's lots in spend order,
     * then from legacy (pre-lot) balance, recording each allocation.
     *
     * @param  CustomerCoinWallet  $wallet  Locked wallet, read before this debit.
     * @param  CoinTransaction  $debit
     * @param  int  $amount
     * @param  int|null  $preferLotId  Drain this lot first (claw-backs).
     * @return int  Coins allocated.
     */
    private function consume(CustomerCoinWallet $wallet, CoinTransaction $debit, int $amount, ?int $preferLotId = null): int
    {
        $lots = $this->ledger->lockSpendableLots((int) $wallet->customer_id, $preferLotId);

        $legacy = max(0, (int) $wallet->balance - (int) $lots->sum('remaining'));
        $left   = $amount;

        foreach ($lots as $lot) {
            if ($left <= 0) {
                break;
            }

            $take = min($left, (int) $lot->remaining);

            $lot->remaining = (int) $lot->remaining - $take;
            $lot->save();

            $this->ledger->allocate($debit, $lot, $take);

            $left -= $take;
        }

        if ($left > 0 && $legacy > 0) {
            $take = min($left, $legacy);

            $this->ledger->allocate($debit, null, $take);

            $left -= $take;
        }

        return $amount - $left;
    }

    /**
     * Expiry timestamp for a newly earned lot (null for every other credit).
     *
     * @param  TransactionType  $type
     * @return Carbon|null
     */
    private function expiryFor(TransactionType $type): ?Carbon
    {
        if ($type !== TransactionType::Earned) {
            return null;
        }

        // Resolved lazily (cached) so constructing this service never hits the DB.
        $days = (int) CoinSetting::active()->expiry_days;

        return $days > 0 ? Carbon::now()->addDays($days) : null;
    }

    /**
     * Guard against non-positive amounts (fail fast).
     *
     * @param  int  $amount
     * @return void
     *
     * @throws InvalidArgumentException
     */
    private function guardPositive(int $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Coin amount must be a positive integer.');
        }
    }
}

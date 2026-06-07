<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Services;

use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Exceptions\InsufficientCoinsException;
use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Models\CoinTransaction;
use Gabha\RewardCoins\Repositories\Contracts\CoinTransactionRepositoryInterface;
use Gabha\RewardCoins\Repositories\Contracts\CoinWalletRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The single writer of coin movements.
 *
 * Every public method records a ledger row AND updates the wallet inside one
 * DB transaction, so the ledger and the materialised balances can never drift.
 * Higher-level policy (caps, eligibility) lives in the calling services.
 */
class CoinWalletService
{
    public function __construct(
        private readonly CoinWalletRepositoryInterface $wallets,
        private readonly CoinTransactionRepositoryInterface $ledger,
    ) {
    }

    /**
     * Add coins to a customer (pending or confirmed) and record the ledger row.
     *
     * @param  int  $customerId
     * @param  int  $amount  Positive coin count.
     * @param  TransactionType  $type
     * @param  int|null  $orderId
     * @param  string  $note
     * @param  TransactionStatus  $status  Pending parks coins; Confirmed makes them spendable.
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
    ): CoinTransaction {
        $this->guardPositive($amount);

        return DB::transaction(function () use ($customerId, $amount, $type, $orderId, $note, $status): CoinTransaction {
            $transaction = $this->ledger->record(
                customerId: $customerId,
                type: $type,
                status: $status,
                amount: $amount,
                orderId: $orderId,
                note: $note !== '' ? $note : null,
                expiresAt: $this->expiryFor($type),
            );

            if ($status === TransactionStatus::Pending) {
                $this->wallets->incrementPending($customerId, $amount);
            } else {
                $this->wallets->incrementBalance($customerId, $amount);
            }

            return $transaction;
        });
    }

    /**
     * Remove coins from a customer's spendable balance and record the ledger row.
     *
     * @param  int  $customerId
     * @param  int  $amount  Positive coin count.
     * @param  TransactionType  $type
     * @param  int|null  $orderId
     * @param  string  $note
     * @param  TransactionStatus  $status
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
        TransactionStatus $status = TransactionStatus::Confirmed,
    ): CoinTransaction {
        $this->guardPositive($amount);

        return DB::transaction(function () use ($customerId, $amount, $type, $orderId, $note, $status): CoinTransaction {
            $balance = $this->wallets->getBalance($customerId);

            if ($balance < $amount) {
                throw InsufficientCoinsException::for($amount, $balance);
            }

            $transaction = $this->ledger->record(
                customerId: $customerId,
                type: $type,
                status: $status,
                amount: $amount,
                orderId: $orderId,
                note: $note !== '' ? $note : null,
            );

            if ($type === TransactionType::Redeemed) {
                $this->wallets->applyRedemption($customerId, $amount);
            } else {
                $this->wallets->decrementBalance($customerId, $amount);
            }

            return $transaction;
        });
    }

    /**
     * Force-remove coins from a customer and record the ledger row, without the
     * sufficiency guard {@see self::debit()} enforces.
     *
     * Used for refund claw-backs, where the customer may already have spent the
     * coins being revoked: the wallet column is floored at zero (never negative)
     * rather than throwing. Records a positive-magnitude ledger row; the signed
     * direction is implied by $type (e.g. Revoked).
     *
     * @param  int  $customerId
     * @param  int  $amount  Positive coin count.
     * @param  TransactionType  $type
     * @param  bool  $fromPending  Debit the pending bucket instead of the spendable balance.
     * @param  int|null  $orderId
     * @param  string  $note
     * @return CoinTransaction
     *
     * @throws InvalidArgumentException
     */
    public function revoke(
        int $customerId,
        int $amount,
        TransactionType $type,
        bool $fromPending = false,
        ?int $orderId = null,
        string $note = '',
    ): CoinTransaction {
        $this->guardPositive($amount);

        return DB::transaction(function () use ($customerId, $amount, $type, $fromPending, $orderId, $note): CoinTransaction {
            $transaction = $this->ledger->record(
                customerId: $customerId,
                type: $type,
                status: TransactionStatus::Confirmed,
                amount: $amount,
                orderId: $orderId,
                note: $note !== '' ? $note : null,
            );

            if ($fromPending) {
                $this->wallets->decrementPending($customerId, $amount);
            } else {
                $this->wallets->decrementBalance($customerId, $amount);
            }

            return $transaction;
        });
    }

    /**
     * Promote a pending earned transaction to confirmed (spendable).
     *
     * Idempotent: a transaction that is not pending is returned untouched.
     *
     * @param  CoinTransaction  $transaction
     * @return CoinTransaction
     */
    public function confirm(CoinTransaction $transaction): CoinTransaction
    {
        if ($transaction->status !== TransactionStatus::Pending) {
            return $transaction;
        }

        return DB::transaction(function () use ($transaction): CoinTransaction {
            $this->wallets->confirmPending((int) $transaction->customer_id, (int) $transaction->amount);

            $transaction->status = TransactionStatus::Confirmed;
            $transaction->save();

            return $transaction;
        });
    }

    /**
     * Confirm every pending coin batch a customer is holding (manual admin
     * approval), moving them all from pending to spendable in one unit of work.
     *
     * Mirrors the automatic delivery-confirm path; lets an admin release a
     * customer's coins without waiting for the order to reach `completed`.
     *
     * @param  int  $customerId
     * @return int  Coins confirmed.
     */
    public function confirmAllForCustomer(int $customerId): int
    {
        return DB::transaction(function () use ($customerId): int {
            $confirmed = 0;

            foreach ($this->ledger->getPendingForCustomer($customerId) as $transaction) {
                $this->confirm($transaction);

                $confirmed += (int) $transaction->amount;
            }

            return $confirmed;
        });
    }

    /**
     * Resolve the expiry timestamp for a newly earned batch (null otherwise).
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

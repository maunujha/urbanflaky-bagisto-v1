<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Repositories\Contracts;

use DateTimeInterface;
use Gabha\RewardCoins\DTOs\CoinEarningPayload;
use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Models\CoinTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Data-access contract for the coin ledger (coin_transactions).
 *
 * Keeping this behind an interface means the storage layer is swappable and
 * the services depend on the abstraction, never on Eloquent directly.
 */
interface CoinTransactionRepositoryInterface
{
    /**
     * Create an `earned` / `pending` ledger row for an order, with expiry set
     * from the active settings.
     *
     * @param  CoinEarningPayload  $payload
     * @param  int  $coins
     * @return CoinTransaction
     */
    public function createEarning(CoinEarningPayload $payload, int $coins): CoinTransaction;

    /**
     * Generic ledger insert used by the wallet service.
     *
     * (Addition to the base spec: the services need a primitive-driven creator;
     * createEarning() is the payload-driven convenience built on top of this.)
     *
     * @param  int  $customerId
     * @param  TransactionType  $type
     * @param  TransactionStatus  $status
     * @param  int  $amount
     * @param  int|null  $orderId
     * @param  string|null  $note
     * @param  DateTimeInterface|null  $expiresAt
     * @return CoinTransaction
     */
    public function record(
        int $customerId,
        TransactionType $type,
        TransactionStatus $status,
        int $amount,
        ?int $orderId = null,
        ?string $note = null,
        ?DateTimeInterface $expiresAt = null,
    ): CoinTransaction;

    /**
     * Pending `earned` transactions tied to an order (for confirm/reverse).
     *
     * @param  int  $orderId
     * @return Collection<int, CoinTransaction>
     */
    public function getPendingForOrder(int $orderId): Collection;

    /**
     * Pending `earned` transactions for a customer (for manual admin approval).
     *
     * @param  int  $customerId
     * @return Collection<int, CoinTransaction>
     */
    public function getPendingForCustomer(int $customerId): Collection;

    /**
     * Confirmed `redeemed` transactions tied to an order (for reverse).
     *
     * (Addition to the base spec: required to restore redeemed coins on
     * cancellation.)
     *
     * @param  int  $orderId
     * @return Collection<int, CoinTransaction>
     */
    public function getRedeemedForOrder(int $orderId): Collection;

    /**
     * Live `earned` transactions tied to an order — both pending and confirmed
     * (for the refund claw-back). Terminal rows (cancelled/expired) are excluded.
     *
     * @param  int  $orderId
     * @return Collection<int, CoinTransaction>
     */
    public function getEarnedForOrder(int $orderId): Collection;

    /**
     * Pending `earned` transactions whose return window has elapsed
     * (available_at set and now past) — the confirm-available sweep input.
     *
     * @return Collection<int, CoinTransaction>
     */
    public function getAvailableForConfirmation(): Collection;

    /**
     * Stamp the return-window unlock time on an order's still-pending earned
     * coins, only where it has not already been set. Idempotent.
     *
     * @param  int  $orderId
     * @param  DateTimeInterface  $availableAt
     * @return int  Rows stamped.
     */
    public function stampAvailableAt(int $orderId, DateTimeInterface $availableAt): int;

    /**
     * Total coin magnitude already recorded for an order under a given type
     * (used to cap repeated partial-refund claw-backs / restores).
     *
     * @param  int  $orderId
     * @param  TransactionType  $type
     * @return int
     */
    public function sumAmountForOrder(int $orderId, TransactionType $type): int;

    /**
     * Paginated, newest-first ledger for a customer (history table).
     *
     * @param  int  $customerId
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function getForCustomer(int $customerId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Confirmed transactions whose expiry has lapsed (for the expiry job).
     *
     * @return Collection<int, CoinTransaction>
     */
    public function getExpired(): Collection;

    /**
     * Total confirmed coins for a customer expiring within the next $days days
     * (drives the "expiring soon" summary card).
     *
     * @param  int  $customerId
     * @param  int  $days
     * @return int
     */
    public function expiringSoonTotal(int $customerId, int $days): int;

    /**
     * Mark an order's still-active coin transactions as reversed/cancelled.
     *
     * Earned-pending rows become `cancelled`; redeemed rows become `reversed`.
     * Wallet balances are adjusted separately by the calling service so the
     * whole reversal is one atomic unit.
     *
     * @param  int  $orderId
     * @return void
     */
    public function reverseForOrder(int $orderId): void;
}

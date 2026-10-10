<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Repositories\Contracts;

use DateTimeInterface;
use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Models\CoinAllocation;
use Gabha\RewardCoins\Models\CoinTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

/**
 * Data-access contract for the coin ledger (coin_transactions) and the lot
 * allocations that tie each debit to the lot(s) it consumed.
 *
 * Keeping this behind an interface means the storage layer is swappable and
 * the services depend on the abstraction, never on Eloquent directly. Methods
 * documented as "locking" must be called inside a DB transaction.
 */
interface CoinTransactionRepositoryInterface
{
    /**
     * Generic ledger insert used by the wallet service.
     *
     * @param  int  $customerId
     * @param  TransactionType  $type
     * @param  TransactionStatus  $status
     * @param  int  $amount
     * @param  int|null  $orderId
     * @param  string|null  $note
     * @param  DateTimeInterface|null  $expiresAt
     * @param  int|null  $remaining  Opening balance of a credit lot; null for debits.
     * @param  string|null  $operationKey  Unique idempotency key.
     * @param  array<string, mixed>  $meta
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
        ?int $remaining = null,
        ?string $operationKey = null,
        array $meta = [],
    ): CoinTransaction;

    /**
     * Record that $debit consumed $amount coins from $lot (null = legacy balance).
     *
     * @param  CoinTransaction  $debit
     * @param  CoinTransaction|null  $lot
     * @param  int  $amount
     * @return CoinAllocation
     */
    public function allocate(CoinTransaction $debit, ?CoinTransaction $lot, int $amount): CoinAllocation;

    /**
     * The row posted under an idempotency key, read with a row lock so the
     * latest committed version is seen (locking).
     *
     * @param  string  $operationKey
     * @return CoinTransaction|null
     */
    public function findByOperationKey(string $operationKey): ?CoinTransaction;

    /**
     * Re-read a single row with a row lock (locking).
     *
     * @param  int  $id
     * @return CoinTransaction|null
     */
    public function lockById(int $id): ?CoinTransaction;

    /**
     * The order's single row of the given type — its earned lot or its
     * redemption — with a row lock (locking).
     *
     * @param  int  $orderId
     * @param  TransactionType  $type
     * @return CoinTransaction|null
     */
    public function lockOrderRow(int $orderId, TransactionType $type): ?CoinTransaction;

    /**
     * All of an order's rows of one type (small, bounded per order).
     *
     * @param  int  $orderId
     * @param  TransactionType  $type
     * @return Collection<int, CoinTransaction>
     */
    public function getOrderRows(int $orderId, TransactionType $type): Collection;

    /**
     * Total coin magnitude already recorded for an order under a given type.
     *
     * @param  int  $orderId
     * @param  TransactionType  $type
     * @return int
     */
    public function sumAmountForOrder(int $orderId, TransactionType $type): int;

    /**
     * A customer's confirmed lots that still hold coins, in spend order:
     * soonest-expiring first, non-expiring last, then oldest first (locking).
     *
     * @param  int  $customerId
     * @param  int|null  $preferLotId  Lot to drain before the FIFO order.
     * @return Collection<int, CoinTransaction>
     */
    public function lockSpendableLots(int $customerId, ?int $preferLotId = null): Collection;

    /**
     * A debit's allocations, newest first, with a row lock (locking).
     *
     * @param  int  $debitId
     * @return Collection<int, CoinAllocation>
     */
    public function lockAllocationsFor(int $debitId): Collection;

    /**
     * Pending `earned` lots for a customer (for manual admin approval).
     *
     * @param  int  $customerId
     * @return Collection<int, CoinTransaction>
     */
    public function getPendingForCustomer(int $customerId): Collection;

    /**
     * Pending `earned` lots whose return window has elapsed, streamed in
     * id-ordered chunks so memory stays flat.
     *
     * @param  int  $chunkSize
     * @return LazyCollection<int, CoinTransaction>
     */
    public function lazyAvailableForConfirmation(int $chunkSize = 200): LazyCollection;

    /**
     * Distinct order ids holding pending `earned` lots with no return-window
     * stamp yet (the delivery reconciliation input), streamed.
     *
     * @param  int  $chunkSize
     * @return LazyCollection<int, int>
     */
    public function lazyOrdersMissingAvailability(int $chunkSize = 200): LazyCollection;

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
     * Confirmed lots whose expiry has lapsed, streamed in id-ordered chunks.
     * Legacy lots without a tracked remainder are excluded (never guessed).
     *
     * @param  int  $chunkSize
     * @return LazyCollection<int, CoinTransaction>
     */
    public function lazyExpiredLots(int $chunkSize = 200): LazyCollection;

    /**
     * Paginated, newest-first ledger for a customer (history table).
     *
     * @param  int  $customerId
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function getForCustomer(int $customerId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Unspent confirmed coins for a customer expiring within the next $days
     * days (drives the "expiring soon" summary card).
     *
     * @param  int  $customerId
     * @param  int  $days
     * @return int
     */
    public function expiringSoonTotal(int $customerId, int $days): int;
}

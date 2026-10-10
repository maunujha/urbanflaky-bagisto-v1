<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Repositories;

use DateTimeInterface;
use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Models\CoinAllocation;
use Gabha\RewardCoins\Models\CoinTransaction;
use Gabha\RewardCoins\Repositories\Contracts\CoinTransactionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

/**
 * Eloquent-backed implementation of the coin ledger repository.
 */
class CoinTransactionRepository implements CoinTransactionRepositoryInterface
{
    /**
     * Ledger types that open a spendable lot when credited.
     *
     * @var array<int, string>
     */
    public const LOT_TYPES = [
        TransactionType::Earned->value,
        TransactionType::Refunded->value,
        TransactionType::Adjusted->value,
    ];

    /**
     * @param  CoinTransaction  $model  Injected so the data layer is swappable.
     * @param  CoinAllocation  $allocations
     */
    public function __construct(
        private readonly CoinTransaction $model,
        private readonly CoinAllocation $allocations,
    ) {
    }

    /**
     * {@inheritDoc}
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
    ): CoinTransaction {
        return $this->model->newQuery()->create([
            'customer_id'   => $customerId,
            'type'          => $type,
            'status'        => $status,
            'amount'        => $amount,
            'remaining'     => $remaining,
            'operation_key' => $operationKey,
            'order_id'      => $orderId,
            'note'          => $note,
            'meta'          => $meta === [] ? null : $meta,
            'expires_at'    => $expiresAt,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function allocate(CoinTransaction $debit, ?CoinTransaction $lot, int $amount): CoinAllocation
    {
        return $this->allocations->newQuery()->create([
            'debit_transaction_id'  => $debit->id,
            'credit_transaction_id' => $lot?->id,
            'amount'                => $amount,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function findByOperationKey(string $operationKey): ?CoinTransaction
    {
        return $this->model->newQuery()
            ->where('operation_key', $operationKey)
            ->lockForUpdate()
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function lockById(int $id): ?CoinTransaction
    {
        return $this->model->newQuery()->whereKey($id)->lockForUpdate()->first();
    }

    /**
     * {@inheritDoc}
     */
    public function lockOrderRow(int $orderId, TransactionType $type): ?CoinTransaction
    {
        return $this->model->newQuery()
            ->where('type', $type->value)
            ->where('order_id', $orderId)
            ->orderBy('id')
            ->lockForUpdate()
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderRows(int $orderId, TransactionType $type): Collection
    {
        return $this->model->newQuery()
            ->where('type', $type->value)
            ->where('order_id', $orderId)
            ->orderBy('id')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function sumAmountForOrder(int $orderId, TransactionType $type): int
    {
        return (int) $this->model->newQuery()
            ->where('type', $type->value)
            ->where('order_id', $orderId)
            ->sum('amount');
    }

    /**
     * {@inheritDoc}
     */
    public function lockSpendableLots(int $customerId, ?int $preferLotId = null): Collection
    {
        $query = $this->model->newQuery()
            ->where('customer_id', $customerId)
            ->where('status', TransactionStatus::Confirmed->value)
            ->whereIn('type', self::LOT_TYPES)
            ->where('remaining', '>', 0);

        if ($preferLotId !== null) {
            $query->orderByRaw('id = ? DESC', [$preferLotId]);
        }

        return $query
            ->orderByRaw('expires_at IS NULL')
            ->orderBy('expires_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function lockAllocationsFor(int $debitId): Collection
    {
        return $this->allocations->newQuery()
            ->with('lot')
            ->where('debit_transaction_id', $debitId)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getPendingForCustomer(int $customerId): Collection
    {
        return $this->model->newQuery()
            ->where('customer_id', $customerId)
            ->where('type', TransactionType::Earned->value)
            ->where('status', TransactionStatus::Pending->value)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function lazyAvailableForConfirmation(int $chunkSize = 200): LazyCollection
    {
        return $this->model->newQuery()
            ->where('type', TransactionType::Earned->value)
            ->where('status', TransactionStatus::Pending->value)
            ->whereNotNull('available_at')
            ->where('available_at', '<=', Carbon::now())
            ->lazyById($chunkSize);
    }

    /**
     * {@inheritDoc}
     */
    public function lazyOrdersMissingAvailability(int $chunkSize = 200): LazyCollection
    {
        return $this->model->newQuery()
            ->select(['id', 'order_id'])
            ->where('type', TransactionType::Earned->value)
            ->where('status', TransactionStatus::Pending->value)
            ->whereNull('available_at')
            ->whereNotNull('order_id')
            ->lazyById($chunkSize)
            ->map(fn (CoinTransaction $row): int => (int) $row->order_id)
            ->unique();
    }

    /**
     * {@inheritDoc}
     */
    public function stampAvailableAt(int $orderId, DateTimeInterface $availableAt): int
    {
        return $this->model->newQuery()
            ->where('order_id', $orderId)
            ->where('type', TransactionType::Earned->value)
            ->where('status', TransactionStatus::Pending->value)
            ->whereNull('available_at')
            ->update(['available_at' => $availableAt]);
    }

    /**
     * {@inheritDoc}
     */
    public function lazyExpiredLots(int $chunkSize = 200): LazyCollection
    {
        return $this->model->newQuery()
            ->whereIn('type', self::LOT_TYPES)
            ->where('status', TransactionStatus::Confirmed->value)
            ->whereNotNull('remaining')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', Carbon::now())
            ->lazyById($chunkSize);
    }

    /**
     * {@inheritDoc}
     */
    public function getForCustomer(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('customer_id', $customerId)
            ->latest()
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function expiringSoonTotal(int $customerId, int $days): int
    {
        return (int) $this->model->newQuery()
            ->where('customer_id', $customerId)
            ->whereIn('type', self::LOT_TYPES)
            ->where('status', TransactionStatus::Confirmed->value)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [Carbon::now(), Carbon::now()->addDays($days)])
            ->sum('remaining');
    }
}

<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Repositories;

use DateTimeInterface;
use Gabha\RewardCoins\DTOs\CoinEarningPayload;
use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Models\CoinTransaction;
use Gabha\RewardCoins\Repositories\Contracts\CoinTransactionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Eloquent-backed implementation of the coin ledger repository.
 */
class CoinTransactionRepository implements CoinTransactionRepositoryInterface
{
    /**
     * @param  CoinTransaction  $model  Injected so the data layer is swappable.
     */
    public function __construct(private readonly CoinTransaction $model)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function createEarning(CoinEarningPayload $payload, int $coins): CoinTransaction
    {
        $expiryDays = (int) CoinSetting::active()->expiry_days;

        return $this->record(
            customerId: $payload->customerId,
            type: TransactionType::Earned,
            status: TransactionStatus::Pending,
            amount: $coins,
            orderId: $payload->orderId,
            note: sprintf('Earned on order #%s', $payload->orderIncrementId),
            expiresAt: $expiryDays > 0 ? Carbon::now()->addDays($expiryDays) : null,
        );
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
    ): CoinTransaction {
        return $this->model->newQuery()->create([
            'customer_id' => $customerId,
            'type'        => $type,
            'status'      => $status,
            'amount'      => $amount,
            'order_id'    => $orderId,
            'note'        => $note,
            'expires_at'  => $expiresAt,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getPendingForOrder(int $orderId): Collection
    {
        return $this->model->newQuery()
            ->where('order_id', $orderId)
            ->where('type', TransactionType::Earned->value)
            ->where('status', TransactionStatus::Pending->value)
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
    public function getRedeemedForOrder(int $orderId): Collection
    {
        return $this->model->newQuery()
            ->where('order_id', $orderId)
            ->where('type', TransactionType::Redeemed->value)
            ->where('status', TransactionStatus::Confirmed->value)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getEarnedForOrder(int $orderId): Collection
    {
        return $this->model->newQuery()
            ->where('order_id', $orderId)
            ->where('type', TransactionType::Earned->value)
            ->whereIn('status', [
                TransactionStatus::Pending->value,
                TransactionStatus::Confirmed->value,
            ])
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableForConfirmation(): Collection
    {
        return $this->model->newQuery()
            ->where('type', TransactionType::Earned->value)
            ->where('status', TransactionStatus::Pending->value)
            ->whereNotNull('available_at')
            ->where('available_at', '<=', Carbon::now())
            ->get();
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
    public function sumAmountForOrder(int $orderId, TransactionType $type): int
    {
        return (int) $this->model->newQuery()
            ->where('order_id', $orderId)
            ->where('type', $type->value)
            ->sum('amount');
    }

    /**
     * {@inheritDoc}
     */
    public function getForCustomer(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('customer_id', $customerId)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getExpired(): Collection
    {
        return $this->model->newQuery()
            ->where('type', TransactionType::Earned->value)
            ->where('status', TransactionStatus::Confirmed->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', Carbon::now())
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function expiringSoonTotal(int $customerId, int $days): int
    {
        return (int) $this->model->newQuery()
            ->where('customer_id', $customerId)
            ->where('type', TransactionType::Earned->value)
            ->where('status', TransactionStatus::Confirmed->value)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [Carbon::now(), Carbon::now()->addDays($days)])
            ->sum('amount');
    }

    /**
     * {@inheritDoc}
     */
    public function reverseForOrder(int $orderId): void
    {
        $this->model->newQuery()
            ->where('order_id', $orderId)
            ->where('type', TransactionType::Earned->value)
            ->where('status', TransactionStatus::Pending->value)
            ->update(['status' => TransactionStatus::Cancelled->value]);

        $this->model->newQuery()
            ->where('order_id', $orderId)
            ->where('type', TransactionType::Redeemed->value)
            ->where('status', TransactionStatus::Confirmed->value)
            ->update(['status' => TransactionStatus::Reversed->value]);
    }
}

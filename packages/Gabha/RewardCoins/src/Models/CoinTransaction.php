<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Models;

use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Sales\Models\OrderProxy;

/**
 * A single immutable ledger entry in a customer's coin history.
 *
 * Every credit/debit is one row; the running wallet balances are derived from
 * these by the wallet service. Data-only model — no business logic lives here.
 */
class CoinTransaction extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'coin_transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'type',
        'status',
        'amount',
        'order_id',
        'note',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type'       => TransactionType::class,
        'status'     => TransactionStatus::class,
        'amount'     => 'integer',
        'expires_at' => 'datetime',
    ];

    /**
     * The customer who owns this transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProxy::modelClass(), 'customer_id');
    }

    /**
     * The order this transaction originated from, if any.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderProxy::modelClass(), 'order_id');
    }

    /**
     * Scope to transactions still awaiting confirmation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::Pending->value);
    }

    /**
     * Scope to confirmed (spendable) transactions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::Confirmed->value);
    }

    /**
     * Scope to a single customer's transactions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $customerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope to coins expiring within the next $days days.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpiringSoon(Builder $query, int $days): Builder
    {
        return $query
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }
}

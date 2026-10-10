<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Links a debit to the lot it consumed.
 *
 * `credit_transaction_id` is NULL when the debit drew on legacy balance that
 * predates lot accounting (not yet backfilled). `restored` counts the coins of
 * this allocation later handed back by a redemption restore. Data-only model.
 */
class CoinAllocation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'coin_allocations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'debit_transaction_id',
        'credit_transaction_id',
        'amount',
        'restored',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount'   => 'integer',
        'restored' => 'integer',
    ];

    /**
     * The debit row that consumed the coins.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function debit(): BelongsTo
    {
        return $this->belongsTo(CoinTransaction::class, 'debit_transaction_id');
    }

    /**
     * The lot the coins came from (null for legacy balance).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(CoinTransaction::class, 'credit_transaction_id');
    }
}

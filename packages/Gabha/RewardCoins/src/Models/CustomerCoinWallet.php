<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Customer\Models\CustomerProxy;

/**
 * A customer's coin wallet: the materialised balances.
 *
 * Pure data model — all mutation (crediting, debiting, recomputing balances)
 * lives in the wallet service, never here.
 */
class CustomerCoinWallet extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customer_coin_wallets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'balance',
        'pending_balance',
        'lifetime_earned',
        'lifetime_redeemed',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'customer_id'       => 'integer',
        'balance'           => 'integer',
        'pending_balance'   => 'integer',
        'lifetime_earned'   => 'integer',
        'lifetime_redeemed' => 'integer',
    ];

    /**
     * The customer who owns this wallet.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProxy::modelClass(), 'customer_id');
    }
}

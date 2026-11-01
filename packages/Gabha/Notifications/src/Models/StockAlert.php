<?php

namespace Gabha\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Models\Product;

/**
 * "Email me when this is back": one pending request per product and address.
 */
class StockAlert extends Model
{
    public const PENDING = 'pending';

    public const NOTIFIED = 'notified';

    public const EXPIRED = 'expired';

    protected $guarded = ['id'];

    protected $casts = [
        'notified_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

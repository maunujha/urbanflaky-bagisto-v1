<?php

declare(strict_types=1);

namespace Gabha\Inventory\Models;

use Gabha\Inventory\Enums\MovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Models\Product;

/**
 * Append-only inventory ledger entry. One row is written for every change to
 * on-hand stock; the inventory aggregate is derived from these rows. Created
 * only through {@see \Gabha\Inventory\Services\StockMovementService}.
 *
 * `qty_before` / `qty_after` snapshot the running stock around the movement
 * (shown as "Previous Stock" / "New Stock" in the history screen).
 */
class StockMovement extends Model
{
    /**
     * Table associated with the model.
     *
     * @var string
     */
    protected $table = 'stock_movements';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'movement_number',
        'product_variant_id',
        'movement_type',
        'quantity',
        'qty_before',
        'qty_after',
        'reference_type',
        'reference_id',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'movement_type' => MovementType::class,
        'quantity'      => 'integer',
        'qty_before'    => 'integer',
        'qty_after'     => 'integer',
    ];

    /**
     * The product variant this movement applies to (soft reference).
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_variant_id');
    }
}

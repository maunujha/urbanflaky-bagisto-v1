<?php

declare(strict_types=1);

namespace Gabha\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Models\Product;

/**
 * Per-variant inventory aggregate (one row per variant).
 *
 * A read-model materialised from the {@see StockMovement} ledger and
 * `purchase_items`. Mutated only through {@see \Gabha\Inventory\Services\InventoryService};
 * never edited by hand.
 */
class Inventory extends Model
{
    /**
     * Table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_variant_id',
        'current_stock',
        'average_cost',
        'inventory_value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'current_stock'   => 'integer',
        'average_cost'    => 'decimal:4',
        'inventory_value' => 'decimal:4',
    ];

    /**
     * The product variant this inventory row tracks. Soft reference — may be
     * null if the variant was removed from the catalog.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_variant_id');
    }
}

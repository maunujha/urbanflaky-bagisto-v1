<?php

declare(strict_types=1);

namespace Gabha\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Models\Product;

/**
 * A single variant line within a {@see Purchase}.
 */
class PurchaseItem extends Model
{
    /**
     * Table associated with the model.
     *
     * @var string
     */
    protected $table = 'purchase_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'purchase_id',
        'product_variant_id',
        'quantity',
        'unit_cost',
        'total_cost',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'quantity'   => 'integer',
        'unit_cost'  => 'decimal:4',
        'total_cost' => 'decimal:4',
    ];

    /**
     * The owning purchase.
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * The purchased product variant (a Bagisto product row). Soft reference —
     * may be null if the variant was later removed from the catalog.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_variant_id');
    }
}

<?php

declare(strict_types=1);

namespace Gabha\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A purchase: stock bought from a vendor, with one row per variant in
 * {@see PurchaseItem}. Totals are persisted snapshots computed server-side at
 * creation time (never trusted from the client).
 */
class Purchase extends Model
{
    /**
     * Table associated with the model.
     *
     * @var string
     */
    protected $table = 'purchases';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'purchase_number',
        'vendor_id',
        'purchase_date',
        'invoice_number',
        'bill_file',
        'total_quantity',
        'total_amount',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'purchase_date'  => 'date',
        'total_quantity' => 'integer',
        'total_amount'   => 'decimal:4',
    ];

    /**
     * The vendor this purchase was made from.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * The line items of this purchase.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Authenticated admin download URL for the uploaded bill (null when none).
     */
    public function getBillUrlAttribute(): ?string
    {
        return $this->bill_file
            ? route('admin.inventory.purchases.bill', $this->id)
            : null;
    }
}

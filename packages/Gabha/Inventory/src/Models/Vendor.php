<?php

declare(strict_types=1);

namespace Gabha\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Vendor (supplier) record.
 *
 * The datagrid's purchase aggregates and the delete guard are still resolved
 * defensively in {@see \Gabha\Inventory\Repositories\VendorRepository} via
 * Schema::hasTable, so the vendor module keeps working even if the Purchase
 * module's table is rolled back.
 */
class Vendor extends Model
{
    /**
     * Table associated with the model.
     *
     * @var string
     */
    protected $table = 'vendors';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'mobile',
        'address',
    ];

    /**
     * Purchases made from this vendor.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
}

<?php

declare(strict_types=1);

namespace Gabha\Inventory\Repositories;

use Gabha\Inventory\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Core\Eloquent\Repository;

/**
 * Data-access layer for vendors.
 *
 * Extends Bagisto's core repository (prettus/l5-repository) so it inherits the
 * project-wide create/update/find/delete contract used by every other module.
 */
class VendorRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return Vendor::class;
    }

    /**
     * Whether the vendor is referenced by any purchase record.
     *
     * Forward-compatible integration seam for the Purchase module: the
     * `purchases` table is owned by a separate (future) module, so the lookup
     * is guarded against the table's absence and resolved at the query-builder
     * level rather than via an Eloquent relationship to a non-existent model.
     * When no purchases table is present the vendor is, by definition, free of
     * purchase records and may be deleted.
     */
    public function hasPurchases(int $vendorId): bool
    {
        if (! Schema::hasTable('purchases')) {
            return false;
        }

        return DB::table('purchases')
            ->where('vendor_id', $vendorId)
            ->exists();
    }
}

<?php

declare(strict_types=1);

namespace Gabha\Inventory\Repositories;

use Gabha\Inventory\Models\Purchase;
use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;

class PurchaseRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return Purchase::class;
    }

    /**
     * Generate the next per-year, zero-padded purchase number,
     * e.g. PUR-2026-000001.
     *
     * Derived from the latest number of the current year. A unique index on
     * `purchase_number` guards against the rare concurrent collision: the
     * conflicting transaction simply rolls back (acceptable for low-concurrency
     * admin use).
     */
    public function generatePurchaseNumber(): string
    {
        $prefix = 'PUR-'.now()->format('Y').'-';

        $last = $this->model
            ->newQuery()
            ->where('purchase_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('purchase_number');

        $sequence = $last
            ? ((int) Str::after($last, $prefix)) + 1
            : 1;

        return $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}

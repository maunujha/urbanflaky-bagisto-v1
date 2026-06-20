<?php

declare(strict_types=1);

namespace Gabha\Inventory\Repositories;

use Gabha\Inventory\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;

class InventoryRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return Inventory::class;
    }

    /**
     * Aggregate figures for the inventory dashboard cards.
     *
     * @return array{total_units: int, total_value: float, low_stock: int, total_vendors: int, threshold: int}
     */
    public function dashboardStats(): array
    {
        $threshold = (int) config('inventory.low_stock_threshold', 10);

        $totals = $this->model
            ->newQuery()
            ->selectRaw('COALESCE(SUM(current_stock), 0) as total_units, COALESCE(SUM(inventory_value), 0) as total_value')
            ->first();

        $lowStock = $this->model
            ->newQuery()
            ->where('current_stock', '<=', $threshold)
            ->count();

        return [
            'total_units'   => (int) $totals->total_units,
            'total_value'   => (float) $totals->total_value,
            'low_stock'     => $lowStock,
            'total_vendors' => DB::table('vendors')->count(),
            'threshold'     => $threshold,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Gabha\Inventory\Services;

use Gabha\Inventory\Enums\MovementType;
use Gabha\Inventory\Models\Inventory;
use Gabha\Inventory\Models\StockMovement;
use Illuminate\Support\Facades\DB;

/**
 * Owns the per-variant inventory aggregate.
 *
 * The inventory row is a read-model derived entirely from the ledgers:
 *   current_stock   = SUM(inbound qty) - SUM(outbound qty)   [stock_movements]
 *   average_cost    = SUM(purchase total_cost) / SUM(purchase qty)  [purchase_items]
 *   inventory_value = current_stock * average_cost
 *
 * It is always recomputed from those sources — never incremented blindly — so
 * it can never drift from the ledger.
 */
class InventoryService
{
    /**
     * Fetch the variant's inventory row locked FOR UPDATE, creating it at zero
     * if absent. Serialises concurrent movements for the same variant.
     */
    public function lockOrInitialize(int $variantId): Inventory
    {
        $inventory = Inventory::query()
            ->where('product_variant_id', $variantId)
            ->lockForUpdate()
            ->first();

        if ($inventory) {
            return $inventory;
        }

        Inventory::firstOrCreate(
            ['product_variant_id' => $variantId],
            ['current_stock' => 0, 'average_cost' => 0, 'inventory_value' => 0],
        );

        return Inventory::query()
            ->where('product_variant_id', $variantId)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Recompute and persist the aggregate for a variant from the ledgers.
     */
    public function recalculate(int $variantId, ?Inventory $inventory = null): Inventory
    {
        $inventory ??= $this->lockOrInitialize($variantId);

        $currentStock = $this->currentStock($variantId);
        $averageCost = $this->averageCost($variantId);

        $inventory->current_stock = $currentStock;
        $inventory->average_cost = $averageCost;
        $inventory->inventory_value = round($currentStock * $averageCost, 4);
        $inventory->save();

        return $inventory;
    }

    /**
     * current_stock = all stock-in - all stock-out (from the movement ledger).
     */
    public function currentStock(int $variantId): int
    {
        $in = (int) StockMovement::query()
            ->where('product_variant_id', $variantId)
            ->whereIn('movement_type', MovementType::inboundValues())
            ->sum('quantity');

        $out = (int) StockMovement::query()
            ->where('product_variant_id', $variantId)
            ->whereIn('movement_type', MovementType::outboundValues())
            ->sum('quantity');

        return $in - $out;
    }

    /**
     * average_cost = total purchase value / total purchased quantity.
     *
     * Returns 0 when nothing has been purchased (avoids division by zero).
     */
    public function averageCost(int $variantId): float
    {
        $row = DB::table('purchase_items')
            ->where('product_variant_id', $variantId)
            ->selectRaw('COALESCE(SUM(total_cost), 0) as total_value, COALESCE(SUM(quantity), 0) as total_qty')
            ->first();

        $quantity = (int) $row->total_qty;

        if ($quantity <= 0) {
            return 0.0;
        }

        return round(((float) $row->total_value) / $quantity, 4);
    }
}

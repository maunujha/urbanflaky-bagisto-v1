<?php

declare(strict_types=1);

namespace Gabha\Inventory\Services;

use Gabha\Inventory\Enums\MovementType;
use Gabha\Inventory\Exceptions\NegativeInventoryException;
use Gabha\Inventory\Models\StockMovement;
use Gabha\Inventory\Repositories\StockMovementRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Product\Jobs\UpdateCreateInventoryIndex;
use Webkul\Product\Models\ProductInventory;

/**
 * THE single entry point for changing stock.
 *
 * Inventory quantity is never edited directly anywhere — every change (a
 * purchase, a marketplace sale, a return, damage) flows through {@see record()},
 * which: validates the movement won't oversell, writes the immutable ledger
 * row, recomputes the inventory aggregate, and mirrors the delta onto Bagisto's
 * storefront stock so the shop stays accurate. All atomic.
 */
class StockMovementService
{
    /**
     * Bagisto keeps storefront stock under marketplace vendor 0.
     */
    private const STOCK_VENDOR_ID = 0;

    /**
     * Create a new service instance.
     */
    public function __construct(
        protected StockMovementRepository $stockMovementRepository,
        protected InventoryService $inventoryService,
    ) {}

    /**
     * Record a stock movement and apply all of its effects atomically.
     *
     * @param  array<string, mixed>  $data     product_variant_id, movement_type, quantity, [reference_type, reference_id, notes]
     * @param  bool                  $reindex  dispatch the salable-qty reindex (set false when batching inside a larger transaction; the caller reindexes once)
     *
     * @throws NegativeInventoryException when the movement would make stock negative.
     */
    public function record(array $data, bool $reindex = true): StockMovement
    {
        $movement = DB::transaction(fn () => $this->persist($data));

        if ($reindex) {
            UpdateCreateInventoryIndex::dispatch([$movement->product_variant_id]);
        }

        return $movement;
    }

    /**
     * Transactional body: guard → ledger row → recompute inventory → storefront.
     *
     * @param  array<string, mixed>  $data
     */
    protected function persist(array $data): StockMovement
    {
        $variantId = (int) $data['product_variant_id'];
        $quantity = (int) $data['quantity'];

        $type = $data['movement_type'] instanceof MovementType
            ? $data['movement_type']
            : MovementType::from((string) $data['movement_type']);

        /* Lock the variant's inventory row so the before/after is race-free. */
        $inventory = $this->inventoryService->lockOrInitialize($variantId);

        $before = (int) $inventory->current_stock;
        $after = $before + ($type->sign() * $quantity);

        /* Prevent negative inventory: reject the oversell before writing. */
        if ($after < 0) {
            throw new NegativeInventoryException($before, $quantity);
        }

        Event::dispatch('inventory.stock_movement.create.before', $data);

        $movement = $this->stockMovementRepository->create([
            'movement_number'    => $this->stockMovementRepository->generateMovementNumber(),
            'product_variant_id' => $variantId,
            'movement_type'      => $type->value,
            'quantity'           => $quantity,
            'qty_before'         => $before,
            'qty_after'          => $after,
            'reference_type'     => $data['reference_type'] ?? null,
            'reference_id'       => $data['reference_id'] ?? null,
            'notes'              => $data['notes'] ?? null,
        ]);

        /* Recompute the aggregate from the ledgers (authoritative). */
        $this->inventoryService->recalculate($variantId, $inventory);

        /* Keep the Bagisto storefront stock in step with real on-hand stock. */
        $this->syncStorefrontStock($variantId, $type->sign() * $quantity);

        Event::dispatch('inventory.stock_movement.create.after', $movement);

        return $movement;
    }

    /**
     * Apply the same signed delta to Bagisto's product_inventories (floored at 0
     * so storefront stock never shows negative).
     */
    protected function syncStorefrontStock(int $variantId, int $delta): void
    {
        $sourceId = $this->inventorySourceId();

        $inventory = ProductInventory::query()
            ->where('product_id', $variantId)
            ->where('inventory_source_id', $sourceId)
            ->where('vendor_id', self::STOCK_VENDOR_ID)
            ->lockForUpdate()
            ->first();

        if ($inventory) {
            $inventory->qty = max(0, (int) $inventory->qty + $delta);
            $inventory->save();

            return;
        }

        ProductInventory::create([
            'product_id'          => $variantId,
            'inventory_source_id' => $sourceId,
            'vendor_id'           => self::STOCK_VENDOR_ID,
            'qty'                 => max(0, $delta),
        ]);
    }

    /**
     * Highest-priority active inventory source, falling back to the default id 1.
     */
    protected function inventorySourceId(): int
    {
        return (int) (InventorySource::query()
            ->where('status', 1)
            ->orderBy('priority')
            ->orderBy('id')
            ->value('id') ?? 1);
    }
}

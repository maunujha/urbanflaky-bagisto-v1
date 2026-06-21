<?php

declare(strict_types=1);

namespace Gabha\Inventory\Services;

use Gabha\Inventory\Enums\MovementType;
use Gabha\Inventory\Models\Purchase;
use Gabha\Inventory\Repositories\PurchaseItemRepository;
use Gabha\Inventory\Repositories\PurchaseRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Product\Jobs\UpdateCreateInventoryIndex;

/**
 * Orchestrates the "save purchase" use case.
 *
 * Everything that mutates state — the purchase, its items and (via
 * {@see StockMovementService}) the stock ledger + inventory — happens inside a
 * single DB transaction. Stock is increased only by recording PURCHASE
 * movements, never by touching inventory directly. On any failure the
 * transaction rolls back and the uploaded bill is removed.
 */
class PurchaseService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        protected PurchaseRepository $purchaseRepository,
        protected PurchaseItemRepository $purchaseItemRepository,
        protected StockMovementService $stockMovementService,
    ) {}

    /**
     * Persist a purchase and apply its stock effects atomically.
     *
     * @param  array<string, mixed>  $data      validated purchase + items payload
     * @param  UploadedFile|null     $billFile  optional uploaded bill document
     */
    public function create(array $data, ?UploadedFile $billFile = null): Purchase
    {
        /* Storage is not transactional: store first, delete on rollback. */
        $billPath = $billFile ? $this->storeBill($billFile) : null;

        try {
            $purchase = DB::transaction(function () use ($data, $billPath) {
                return $this->persist($data, $billPath);
            });
        } catch (\Throwable $e) {
            if ($billPath) {
                Storage::delete($billPath);
            }

            throw $e;
        }

        /* Refresh salable-qty index once for all affected variants (post-commit). */
        UpdateCreateInventoryIndex::dispatch(
            $purchase->items->pluck('product_variant_id')->unique()->values()->all()
        );

        return $purchase;
    }

    /**
     * The transactional body: purchase → items → PURCHASE stock movements.
     *
     * @param  array<string, mixed>  $data
     */
    protected function persist(array $data, ?string $billPath): Purchase
    {
        $items = $data['items'];

        [$totalQuantity, $totalAmount] = $this->totals($items);

        Event::dispatch('inventory.purchase.create.before');

        $purchase = $this->purchaseRepository->create([
            'purchase_number' => $this->purchaseRepository->generatePurchaseNumber(),
            'vendor_id'       => $data['vendor_id'],
            'purchase_date'   => $data['purchase_date'],
            'invoice_number'  => $data['invoice_number'] ?? null,
            'bill_file'       => $billPath,
            'total_quantity'  => $totalQuantity,
            'total_amount'    => $totalAmount,
            'notes'           => $data['notes'] ?? null,
        ]);

        foreach ($items as $item) {
            /*
             * Create the line FIRST so average-cost recalculation (driven by the
             * PURCHASE movement below) sees it.
             */
            $this->addLineItem($purchase, $item);
        }

        Event::dispatch('inventory.purchase.create.after', $purchase);

        return $purchase;
    }

    /**
     * Append new line items to an existing purchase (the original lines,
     * vendor, and dates stay immutable — this only adds new ones).
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public function addItems(Purchase $purchase, array $items): Purchase
    {
        DB::transaction(function () use ($purchase, $items) {
            foreach ($items as $item) {
                $this->addLineItem($purchase, $item);
            }

            /*
             * Recompute from the full item set (not an increment) so totals
             * are always derived from the source of truth, the same way
             * persist() derives them — keeps the invariant self-correcting
             * if a future feature ever edits/removes a line item.
             */
            [$totalQuantity, $totalAmount] = $this->totals($purchase->items()->get()->toArray());

            $purchase->update([
                'total_quantity' => $totalQuantity,
                'total_amount'   => $totalAmount,
            ]);
        });

        UpdateCreateInventoryIndex::dispatch(
            collect($items)->pluck('product_variant_id')->unique()->values()->all()
        );

        return $purchase->refresh();
    }

    /**
     * Create one purchase line item and its corresponding PURCHASE stock
     * movement. Shared by create() and addItems() so the line-item shape and
     * stock-movement payload can never drift between the two flows.
     *
     * @param  array<string, mixed>  $item
     */
    protected function addLineItem(Purchase $purchase, array $item): void
    {
        $variantId = (int) $item['product_variant_id'];
        $quantity = (int) $item['quantity'];
        $unitCost = (float) $item['unit_cost'];

        $this->purchaseItemRepository->create([
            'purchase_id'        => $purchase->id,
            'product_variant_id' => $variantId,
            'quantity'           => $quantity,
            'unit_cost'          => $unitCost,
            'total_cost'         => $quantity * $unitCost,
        ]);

        /* Stock is increased only through the movement pipeline. */
        $this->stockMovementService->record([
            'product_variant_id' => $variantId,
            'movement_type'      => MovementType::PURCHASE,
            'quantity'           => $quantity,
            'reference_type'     => 'purchase',
            'reference_id'       => $purchase->id,
            'notes'              => $purchase->notes,
        ], reindex: false);
    }

    /**
     * Sum line items into [total_quantity, total_amount]. Items may be plain
     * validated-request arrays (string keys) or PurchaseItem model arrays
     * (from ->toArray()) — both carry 'quantity' and either 'unit_cost' or
     * 'total_cost'.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{0: int, 1: float}
     */
    protected function totals(array $items): array
    {
        $totalQuantity = 0;
        $totalAmount = 0.0;

        foreach ($items as $item) {
            $quantity = (int) $item['quantity'];
            $unitCost = isset($item['unit_cost']) ? (float) $item['unit_cost'] : null;

            $totalQuantity += $quantity;
            $totalAmount += $unitCost !== null ? $quantity * $unitCost : (float) $item['total_cost'];
        }

        return [$totalQuantity, $totalAmount];
    }

    /**
     * Store the uploaded bill on the (private) default disk under a random,
     * unguessable filename. Returns the stored relative path.
     */
    protected function storeBill(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return $file->storeAs('purchases/bills', Str::random(40).'.'.$extension);
    }
}

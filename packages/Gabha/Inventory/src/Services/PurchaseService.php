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
            $variantId = (int) $item['product_variant_id'];
            $quantity = (int) $item['quantity'];
            $unitCost = (float) $item['unit_cost'];

            /*
             * Create the line FIRST so average-cost recalculation (driven by the
             * PURCHASE movement below) sees it.
             */
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
        [$additionalQuantity, $additionalAmount] = $this->totals($items);

        DB::transaction(function () use ($purchase, $items, $additionalQuantity, $additionalAmount) {
            foreach ($items as $item) {
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

                $this->stockMovementService->record([
                    'product_variant_id' => $variantId,
                    'movement_type'      => MovementType::PURCHASE,
                    'quantity'           => $quantity,
                    'reference_type'     => 'purchase',
                    'reference_id'       => $purchase->id,
                    'notes'              => $purchase->notes,
                ], reindex: false);
            }

            $purchase->increment('total_quantity', $additionalQuantity);
            $purchase->increment('total_amount', $additionalAmount);
        });

        UpdateCreateInventoryIndex::dispatch(
            collect($items)->pluck('product_variant_id')->unique()->values()->all()
        );

        return $purchase->refresh();
    }

    /**
     * Sum the line items into [total_quantity, total_amount].
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
            $unitCost = (float) $item['unit_cost'];

            $totalQuantity += $quantity;
            $totalAmount += $quantity * $unitCost;
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

<?php

namespace Gabha\Inventory\Http\Controllers\Admin;

use Gabha\Inventory\Models\Inventory;
use Gabha\Inventory\Support\VariantPresenter;
use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Product\Models\Product;

/**
 * Shared product-variant autocomplete used by the purchase and stock-movement
 * create screens, so a SKU is never typed by hand. Returns variant-level
 * results with a composed label and the variant's current on-hand stock.
 */
class VariantController extends Controller
{
    /**
     * Search catalog variants by name or SKU.
     */
    public function search(): JsonResponse
    {
        $query = trim((string) request('query'));

        if (mb_strlen($query) < 2) {
            return new JsonResponse(['data' => []]);
        }

        $variants = Product::query()
            ->where('type', 'simple')
            ->where(function ($builder) use ($query) {
                $builder
                    ->where('sku', 'like', "%{$query}%")
                    ->orWhereHas('product_flats', fn ($flat) => $flat->where('name', 'like', "%{$query}%"))
                    ->orWhereHas('parent.product_flats', fn ($flat) => $flat->where('name', 'like', "%{$query}%"));
            })
            ->with([
                'product_flats',
                'parent.product_flats',
                'parent.super_attributes.options',
            ])
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        /* One query for stock levels — avoids an N+1 across the result set. */
        $stocks = Inventory::query()
            ->whereIn('product_variant_id', $variants->pluck('id'))
            ->pluck('current_stock', 'product_variant_id');

        $data = $variants->map(fn ($variant) => [
            'id'            => $variant->id,
            'sku'           => $variant->sku,
            'label'         => VariantPresenter::label($variant),
            'current_stock' => (int) ($stocks[$variant->id] ?? 0),
        ])->values();

        return new JsonResponse(['data' => $data]);
    }
}

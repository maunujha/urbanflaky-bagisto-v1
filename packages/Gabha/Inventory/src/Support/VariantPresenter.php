<?php

declare(strict_types=1);

namespace Gabha\Inventory\Support;

use Webkul\Product\Models\Product;

/**
 * Builds the human-readable label for a product variant, e.g.
 * "Black Oversized T-Shirt - Black - XL (sku-123)".
 *
 * Single source of truth shared by the variant search endpoint and the
 * purchase detail view, so the catalog SKU never has to be typed by hand.
 */
class VariantPresenter
{
    /**
     * Compose the display label for a variant (or standalone simple product).
     */
    public static function label(Product $variant): string
    {
        $parent = $variant->parent;

        /* Standalone simple product: just its own name. */
        if (! $parent) {
            $base = $variant->name ?: $variant->sku;

            return trim($base).' ('.$variant->sku.')';
        }

        $parts = [$parent->name];

        foreach ($parent->super_attributes as $attribute) {
            $optionId = $variant->{$attribute->code};

            $option = $attribute->options->firstWhere('id', $optionId);

            if ($option) {
                $parts[] = $option->label ?: $option->admin_name;
            }
        }

        return implode(' - ', array_filter($parts)).' ('.$variant->sku.')';
    }
}

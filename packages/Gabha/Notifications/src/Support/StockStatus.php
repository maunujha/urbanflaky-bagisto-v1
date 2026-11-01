<?php

namespace Gabha\Notifications\Support;

use Webkul\Product\Models\Product;

/**
 * Whether a sellable product (a variant, or a stand-alone simple product) can
 * be bought right now, and whether a customer may ask to hear when it can.
 */
class StockStatus
{
    public static function inStock(Product $product): bool
    {
        if (! $product->status || ($product->parent && ! $product->parent->status)) {
            return false;
        }

        return (bool) $product->getTypeInstance()->haveSufficientQuantity(1);
    }

    /**
     * Sold out, but still a live product (not switched off or a parent).
     */
    public static function canWaitFor(Product $product): bool
    {
        if ($product->type === 'configurable' || ! $product->status) {
            return false;
        }

        if ($product->parent && ! $product->parent->status) {
            return false;
        }

        return ! self::inStock($product);
    }

    /**
     * The sold-out choices to offer on a product page.
     *
     * @return array<int, string> product id => option label ("" for a simple product)
     */
    public static function soldOutChoices(Product $product): array
    {
        if ($product->type === 'configurable') {
            $choices = [];

            foreach ($product->variants as $variant) {
                if (self::canWaitFor($variant)) {
                    $choices[$variant->id] = ProductPresenter::optionLabel($variant) ?: $variant->sku;
                }
            }

            return $choices;
        }

        if ($product->type === 'simple' && self::canWaitFor($product)) {
            return [$product->id => ''];
        }

        return [];
    }
}

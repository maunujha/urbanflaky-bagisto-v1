<?php

namespace Gabha\Notifications\Support;

use Webkul\Product\Models\Product;

/**
 * What an email needs to show a product: picture, name, option label, link.
 * Variants carry no images or URL of their own, so both come from the parent.
 */
class ProductPresenter
{
    public static function imageUrl(?Product $product): ?string
    {
        $source = $product?->images?->count() ? $product : $product?->parent;

        if (! $source?->images?->count()) {
            return null;
        }

        return product_image()->getProductBaseImage($source)['medium_image_url'] ?? null;
    }

    public static function url(?Product $product): ?string
    {
        $urlKey = $product?->parent?->url_key ?: $product?->url_key;

        return $urlKey ? route('shop.product_or_category.index', $urlKey) : null;
    }

    public static function name(Product $product): string
    {
        return (string) ($product->parent?->name ?: $product->name);
    }

    /**
     * "Black / L" for a variant, "" for a stand-alone product.
     */
    public static function optionLabel(Product $product): string
    {
        $parent = $product->parent;

        if (! $parent) {
            return '';
        }

        $labels = [];

        foreach ($parent->super_attributes as $attribute) {
            $option = $attribute->options->firstWhere('id', $product->{$attribute->code});

            if ($option) {
                $labels[] = $option->label ?: $option->admin_name;
            }
        }

        return implode(' / ', $labels);
    }
}

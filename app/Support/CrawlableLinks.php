<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Webkul\Category\Models\Category;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Product\Repositories\ProductRepository;

/**
 * Server-rendered links for crawlers that do not execute JavaScript.
 *
 * The header menu and category product grids are Vue components fed by API
 * calls, so their raw HTML carries no <a> tags and Ahrefs / AI crawlers
 * reported those pages as orphans. These helpers return the same data the
 * Vue components fetch, for rendering inside their mount placeholders — Vue
 * replaces the placeholder on mount, so users see no difference.
 */
class CrawlableLinks
{
    /**
     * Visible category tree for the current channel (same source as the
     * shop.api.categories.tree endpoint behind the header menu).
     */
    public static function categoryTree(): Collection
    {
        return collect(app(CategoryRepository::class)->getVisibleCategoryTree(
            core()->getCurrentChannel()->root_category_id
        ));
    }

    /**
     * First listing page of a category, with the same filters, default sort
     * and per-page limit as the shop.api.products.index call in v-category.
     *
     * @return array<int, array{name: string, url: string}>
     */
    public static function categoryProducts(Category $category): array
    {
        $products = app(ProductRepository::class)
            ->setSearchEngine('database')
            ->getAll([
                'category_id'          => $category->id,
                'channel_id'           => core()->getCurrentChannel()->id,
                'status'               => 1,
                'visible_individually' => 1,
            ]);

        return collect($products->items())
            ->filter(fn ($product) => $product->url_key && $product->name)
            ->map(fn ($product) => [
                'name' => $product->name,
                'url'  => route('shop.product_or_category.index', $product->url_key),
            ])
            ->values()
            ->all();
    }
}

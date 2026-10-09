<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\Category\Models\Category;
use Webkul\Category\Repositories\CategoryRepository;

/**
 * Server-rendered links for crawlers that do not execute JavaScript.
 *
 * The header menu and category product grids are Vue components fed by API
 * calls, so their raw HTML carries no <a> tags and Ahrefs / AI crawlers
 * reported those pages as orphans. These helpers return the same links for
 * rendering inside the components' mount placeholders — Vue replaces the
 * placeholder on mount, so users see no difference.
 *
 * Because the links now live in server HTML, they are also stored by the
 * full-page cache; invalidate() keeps both caches in step with catalog edits.
 */
class CrawlableLinks
{
    /**
     * Visible category tree for the current channel/locale as plain arrays
     * (name, url, children). Same source as the header's tree API; cached
     * because it renders on every storefront page.
     *
     * @return array<int, array{name: string, url: string, children: array}>
     */
    public static function categoryTree(): array
    {
        return Cache::rememberForever(self::treeKey(core()->getCurrentChannel()->code, app()->getLocale()), function () {
            $map = function ($categories) use (&$map) {
                return collect($categories)->map(fn ($category) => [
                    'name'     => $category->name,
                    'url'      => $category->url,
                    'children' => $map($category->children),
                ])->values()->all();
            };

            return $map(app(CategoryRepository::class)->getVisibleCategoryTree(
                core()->getCurrentChannel()->root_category_id
            ));
        });
    }

    /**
     * Links to the category's visible products (first listing page's worth),
     * read straight from product_flat — only name + url_key are needed.
     *
     * @return array<int, array{name: string, url: string}>
     */
    public static function categoryProducts(Category $category): array
    {
        return DB::table('product_flat as pf')
            ->join('product_categories as pc', 'pc.product_id', '=', 'pf.product_id')
            ->where('pc.category_id', $category->id)
            ->where('pf.channel', core()->getCurrentChannel()->code)
            ->where('pf.locale', app()->getLocale())
            ->where('pf.status', 1)
            ->where('pf.visible_individually', 1)
            ->whereNull('pf.parent_id')
            ->whereNotNull('pf.url_key')
            ->whereNotNull('pf.name')
            ->orderByDesc('pf.created_at')
            ->limit(self::perPage())
            ->get(['pf.name', 'pf.url_key'])
            ->map(fn ($product) => [
                'name' => $product->name,
                'url'  => route('shop.product_or_category.index', $product->url_key),
            ])
            ->all();
    }

    /**
     * A category changed: the menu on every cached page is stale, so drop the
     * tree cache and the whole full-page cache (category edits are rare).
     */
    public static function invalidateCategories(): void
    {
        foreach (core()->getAllChannels() as $channel) {
            foreach (core()->getAllLocales() as $locale) {
                Cache::forget(self::treeKey($channel->code, $locale->code));
            }
        }

        ResponseCache::clear();
    }

    /**
     * A product changed: forget the cached pages of the categories that list it.
     */
    public static function invalidateProductCategories(int $productId): void
    {
        $slugs = DB::table('product_categories as pc')
            ->join('category_translations as ct', 'ct.category_id', '=', 'pc.category_id')
            ->where('pc.product_id', $productId)
            ->whereNotNull('ct.slug')
            ->pluck('ct.slug')
            ->unique()
            ->map(fn ($slug) => '/'.$slug)
            ->all();

        if ($slugs) {
            ResponseCache::forget($slugs);
        }
    }

    protected static function treeKey(string $channel, string $locale): string
    {
        return "crawlable_links.category_tree.{$channel}.{$locale}";
    }

    /**
     * Storefront default page size (first option of products_per_page).
     */
    protected static function perPage(): int
    {
        $options = (string) core()->getConfigData('catalog.products.storefront.products_per_page');

        return (int) (explode(',', $options)[0] ?: 0) ?: 12;
    }
}

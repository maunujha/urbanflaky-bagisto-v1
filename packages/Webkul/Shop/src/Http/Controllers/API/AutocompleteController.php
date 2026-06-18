<?php

namespace Webkul\Shop\Http\Controllers\API;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AutocompleteController extends APIController
{
    public function search(): JsonResponse
    {
        $query = trim(request()->input('query', ''));
        $minLength = max(2, (int) core()->getConfigData('catalog.products.search.min_query_length'));

        if (mb_strlen($query) < $minLength) {
            return response()->json([]);
        }

        $configLimit = (int) (core()->getConfigData('catalog.products.search.autocomplete_limit') ?: 8);
        $requested   = (int) request()->input('limit', $configLimit);
        $limit       = min($configLimit, max(1, $requested));
        $channel = core()->getCurrentChannel()->code;
        $locale = app()->getLocale();

        /**
         * When Meilisearch is the active engine, resolve the typo-tolerant,
         * relevance-ranked product ids through it and hydrate the same flat
         * columns. Any failure (or a different engine) falls back to the native
         * product_flat LIKE lookup below, so autocomplete never goes dark.
         */
        $products = null;

        if ($this->meilisearchActive()) {
            try {
                $products = $this->meilisearchSuggestions($query, $limit, $channel, $locale);
            } catch (\Throwable $e) {
                app(\Gabha\Search\Services\SearchService::class)
                    ->logError('Autocomplete Meilisearch lookup failed; falling back to database.', $e);

                $products = null;
            }
        }

        if ($products === null) {
            $products = $this->databaseSuggestions($query, $limit, $channel, $locale);
        }

        $now = Carbon::now();

        $results = $products->map(function ($product) use ($now) {
            $price = (float) $product->price;
            $hasSpecial = false;

            if ($product->special_price) {
                $from = $product->special_price_from ? Carbon::parse($product->special_price_from) : null;
                $to   = $product->special_price_to   ? Carbon::parse($product->special_price_to)   : null;

                if (($from === null || $now->gte($from)) && ($to === null || $now->lte($to))) {
                    $hasSpecial = true;
                    $price = (float) $product->special_price;
                }
            }

            return [
                'id'             => $product->id,
                'name'           => $product->name,
                'url'            => route('shop.product_or_category.index', $product->url_key),
                'image'          => $product->image_path ? Storage::url($product->image_path) : null,
                'price'          => core()->formatPrice($price),
                'original_price' => $hasSpecial ? core()->formatPrice((float) $product->price) : null,
            ];
        });

        return response()->json($results);
    }

    /**
     * Whether the Gabha Search (Meilisearch) engine is enabled and active.
     */
    protected function meilisearchActive(): bool
    {
        return config('gabha-search.enabled', true)
            && core()->getConfigData('catalog.products.search.engine') === config('gabha-search.engine_value', 'meilisearch');
    }

    /**
     * Resolve suggestions via Meilisearch, then hydrate the flat columns in the
     * relevance order Meilisearch returned. Returns an empty collection for no
     * matches (so the caller does NOT fall back to the DB for a genuine miss).
     */
    protected function meilisearchSuggestions(string $query, int $limit, string $channel, string $locale): \Illuminate\Support\Collection
    {
        $ids = \Gabha\Search\Models\Product::search($query)
            ->take($limit)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($ids)) {
            return collect();
        }

        $rows = $this->suggestionQuery($channel, $locale)
            ->whereIn('pf.product_id', $ids)
            ->get()
            ->keyBy(fn ($row) => (int) $row->id);

        return collect($ids)
            ->map(fn ($id) => $rows->get($id))
            ->filter()
            ->values();
    }

    /**
     * Native product_flat LIKE lookup (the original, default behaviour).
     */
    protected function databaseSuggestions(string $query, int $limit, string $channel, string $locale): \Illuminate\Support\Collection
    {
        return $this->suggestionQuery($channel, $locale)
            ->where('pf.name', 'LIKE', '%'.$query.'%')
            ->orderByRaw('CASE WHEN pf.name LIKE ? THEN 0 ELSE 1 END', [$query.'%'])
            ->limit($limit)
            ->get();
    }

    /**
     * Shared base query selecting the columns both suggestion paths return.
     */
    protected function suggestionQuery(string $channel, string $locale): \Illuminate\Database\Query\Builder
    {
        return DB::table('product_flat as pf')
            ->leftJoin('product_images as pi', function ($join) {
                $join->on('pi.product_id', '=', 'pf.product_id')
                    ->whereRaw('pi.id = (SELECT MIN(id) FROM product_images WHERE product_id = pf.product_id)');
            })
            ->select([
                'pf.product_id as id',
                'pf.name',
                'pf.url_key',
                'pf.price',
                'pf.special_price',
                'pf.special_price_from',
                'pf.special_price_to',
                'pi.path as image_path',
            ])
            ->where('pf.status', 1)
            ->where('pf.visible_individually', 1)
            ->where('pf.channel', $channel)
            ->where('pf.locale', $locale);
    }
}

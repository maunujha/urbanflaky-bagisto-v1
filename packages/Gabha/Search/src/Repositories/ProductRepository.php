<?php

declare(strict_types=1);

namespace Gabha\Search\Repositories;

use Gabha\Search\Services\NaturalLanguage\FilterSet;
use Gabha\Search\Services\NaturalLanguage\IntentFilterBuilder;
use Gabha\Search\Services\NaturalLanguage\QueryParser;
use Gabha\Search\Services\NaturalLanguage\SearchContext;
use Gabha\Search\Services\SearchAnalytics;
use Gabha\Search\Services\SearchService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Throwable;
use Webkul\Product\Repositories\ProductRepository as BaseProductRepository;

/**
 * Drop-in replacement for the core product repository that adds Meilisearch as
 * a third search engine. Bound over the core class in the container, so every
 * storefront/admin call that resolves ProductRepository transparently gains the
 * Meilisearch path.
 *
 * It only intercepts genuine text searches when the admin engine is set to
 * `meilisearch`; category browsing and faceted refinement (or any failure) fall
 * straight through to the untouched parent (database / elastic), which keeps the
 * change additive and instantly reversible via the admin engine toggle.
 */
class ProductRepository extends BaseProductRepository
{
    /**
     * Query params this engine knows how to translate. A request carrying any
     * other non-empty filter (a colour/brand/size facet, etc.) is delegated to
     * the native engine so faceted search keeps working exactly as before.
     *
     * @var array<int, string>
     */
    protected array $supportedParams = [
        'query', 'name', 'category_id', 'price', 'sort', 'order', 'limit', 'mode',
        'page', 'channel_id', 'status', 'visible_individually', 'url_key', 'type',
        'exclude_customizable_products', 'suggest', 'search',
    ];

    /**
     * Route to Meilisearch when appropriate, otherwise defer to the parent.
     */
    public function getAll(array $params = [])
    {
        if ($this->shouldUseMeilisearch($params)) {
            try {
                return $this->searchFromMeilisearch($params);
            } catch (Throwable $e) {
                app(SearchService::class)->logError(
                    'Meilisearch search failed; falling back to the database engine.',
                    $e
                );
            }
        }

        return parent::getAll($params);
    }

    /**
     * Meilisearch drives search only for real text queries while the admin
     * engine is `meilisearch`. Listings and unsupported facets stay native.
     */
    protected function shouldUseMeilisearch(array $params): bool
    {
        if (! config('gabha-search.enabled', true)) {
            return false;
        }

        if (core()->getConfigData('catalog.products.search.engine') !== config('gabha-search.engine_value', 'meilisearch')) {
            return false;
        }

        if (trim((string) ($params['query'] ?? '')) === '') {
            return false;
        }

        $active = array_keys(array_filter(
            $params,
            fn ($value) => $value !== null && $value !== '' && $value !== []
        ));

        return empty(array_diff($active, $this->supportedParams));
    }

    /**
     * Search Meilisearch for ids, then hydrate the products from the database in
     * the exact relevance order Meilisearch returned (FIELD()), wrapped in a
     * LengthAwarePaginator — identical contract to searchFromElastic(), so the
     * resources/Vue frontend need no changes.
     *
     * The natural-language layer sits in front of the server call: the raw query
     * is parsed into structured intent (colour / price / gender / section), which
     * becomes Meilisearch filters while the residual words drive the full-text
     * search. Zero-result NL searches are progressively relaxed, and every search
     * is recorded for analytics — all without changing the return contract.
     */
    public function searchFromMeilisearch(array $params = [])
    {
        $currentPage = Paginator::resolveCurrentPage('page');

        $limit = $this->getPerPageLimit($params);

        $sortOptions = $this->getSortOptions($params);

        $rawQuery = (string) ($params['query'] ?? '');

        $nlEnabled = (bool) config('gabha-search.natural_language.enabled', true);

        $intent = app(QueryParser::class)->parse($rawQuery);

        $searchQuery = $nlEnabled ? $intent->cleanQuery : $rawQuery;

        $filterSet = app(IntentFilterBuilder::class)->build($params, $intent);

        [$indices, $appliedFilter, $relaxedTo] = $this->runMeilisearch($searchQuery, [
            'page'  => $currentPage,
            'limit' => $limit,
            'sort'  => $this->meilisearchSort($sortOptions),
        ], $filterSet, $nlEnabled);

        /**
         * Hand the parsed intent + relaxation to the request-scoped context so the
         * Shop products API can surface shopper-facing feedback in this response.
         */
        if ($nlEnabled) {
            app(SearchContext::class)->record($intent, $relaxedTo, $indices['total']);
        }

        /**
         * Record once per search (first page only, so pagination does not inflate
         * the counts). Fire-and-forget — SearchAnalytics swallows its own errors.
         */
        if ($currentPage === 1) {
            app(SearchAnalytics::class)->record($intent, [
                'results_count' => $indices['total'],
                'relaxed_to'    => $relaxedTo,
                'filters'       => $appliedFilter,
                'channel'       => core()->getCurrentChannelCode(),
                'locale'        => app()->getLocale(),
                'customer_id'   => auth()->guard('customer')->id(),
            ]);
        }

        $query = $this->with([
            'attribute_family',
            'images',
            'videos',
            'attribute_values',
            'price_indices',
            'inventory_indices',
            'reviews',
            'variants',
            'variants.attribute_family',
            'variants.attribute_values',
            'variants.price_indices',
            'variants.inventory_indices',
        ])->scopeQuery(function ($query) use ($params, $indices) {
            $qb = $query->distinct()
                ->select('products.*')
                ->whereIn('products.id', $indices['ids'] ?: [0]);

            if (
                ! empty($params['type'])
                && $params['type'] === 'simple'
                && ! empty($params['exclude_customizable_products'])
            ) {
                $qb->leftJoin('product_customizable_options', 'products.id', '=', 'product_customizable_options.product_id')
                    ->whereNull('product_customizable_options.id');
            }

            if (! empty($indices['ids'])) {
                $table = DB::getTablePrefix().$query->getModel()->getTable();

                $qb->orderBy(DB::raw('FIELD('.$table.'.id, '.implode(',', $indices['ids']).')'));
            }

            return $qb;
        });

        $items = $indices['total'] ? $query->get() : [];

        return new LengthAwarePaginator($items, $indices['total'], $limit, $currentPage, [
            'path'  => request()->url(),
            'query' => $params,
        ]);
    }

    /**
     * Translate Bagisto's toolbar sort into a Meilisearch sort expression. An
     * unsupported / random sort returns no sort so relevance ranking applies.
     *
     * @param  array{sort?:string,order?:string}  $sortOptions
     * @return array<int, string>
     */
    protected function meilisearchSort(array $sortOptions): array
    {
        $sortable = array_values((array) config('gabha-search.sortable_attributes', []));

        $sort = $sortOptions['sort'] ?? null;

        $order = strtolower((string) ($sortOptions['order'] ?? 'desc'));

        if (! in_array($sort, $sortable, true) || ! in_array($order, ['asc', 'desc'], true)) {
            return [];
        }

        return [$sort.':'.$order];
    }

    /**
     * Execute the Meilisearch read with progressive filter relaxation.
     *
     * The first attempt applies every filter (explicit + inferred). If that
     * returns nothing and the query carried inferred (NL) filters, those are
     * dropped one relaxation tier at a time (config order: colour → price →
     * category) and the search retried, so a slightly-wrong guess still lands
     * the shopper on relevant products instead of an empty page. The shopper's
     * own explicit facet choices live in the FilterSet's fixed bucket and are
     * never relaxed.
     *
     * @param  array{page:int,limit:int,sort:array<int,string>}  $base
     * @return array{0: array{ids:array<int,int>,total:int}, 1: string|null, 2: string|null}
     *         [indices, filter expression actually used, relaxation applied (csv|null)]
     */
    protected function runMeilisearch(string $query, array $base, FilterSet $filterSet, bool $nlEnabled): array
    {
        $search = app(SearchService::class);

        $appliedFilter = $filterSet->expression();

        $indices = $search->search($query, $base + ['filter' => $appliedFilter]);

        $relaxedTo = null;

        $relax = (array) config('gabha-search.natural_language.relaxation', []);

        if (
            $nlEnabled
            && ($relax['enabled'] ?? true)
            && $indices['total'] === 0
            && $filterSet->hasNlFilters()
        ) {
            $dropped = [];

            foreach ($filterSet->relaxableTiers((array) ($relax['order'] ?? [])) as $tier) {
                $dropped[] = $tier;

                $appliedFilter = $filterSet->expression($dropped);

                $indices = $search->search($query, $base + ['filter' => $appliedFilter]);

                if ($indices['total'] > 0) {
                    break;
                }
            }

            $relaxedTo = implode(',', $dropped);
        }

        return [$indices, $appliedFilter, $relaxedTo];
    }
}

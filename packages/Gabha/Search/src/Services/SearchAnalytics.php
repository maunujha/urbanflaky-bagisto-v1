<?php

declare(strict_types=1);

namespace Gabha\Search\Services;

use Gabha\Search\Jobs\RecordSearchQuery;
use Gabha\Search\Services\NaturalLanguage\SearchIntent;

/**
 * The single entry point for recording a storefront search.
 *
 * Decides whether a search is worth recording (analytics enabled, driver not
 * 'null', term long enough) and, if so, assembles the flat row and hands it to
 * a queued {@see RecordSearchQuery} job so the request never blocks on it.
 */
class SearchAnalytics
{
    /**
     * Record a parsed search and its outcome.
     *
     * @param  array{results_count?:int,relaxed_to?:string|null,filters?:string|null,channel?:string|null,locale?:string|null,customer_id?:int|null}  $outcome
     */
    public function record(SearchIntent $intent, array $outcome = []): void
    {
        if (! config('gabha-search.analytics.enabled', true)) {
            return;
        }

        if ((string) config('gabha-search.analytics.driver', 'database') === 'null') {
            return;
        }

        $minLength = (int) config('gabha-search.analytics.min_length', 2);

        if (mb_strlen($intent->original) < max(1, $minLength)) {
            return;
        }

        RecordSearchQuery::dispatch($this->toRow($intent, $outcome));
    }

    /**
     * Flatten the intent + outcome into the search_queries column set.
     *
     * @param  array<string, mixed>  $outcome
     * @return array<string, mixed>
     */
    protected function toRow(SearchIntent $intent, array $outcome): array
    {
        return [
            'term'          => $intent->original,
            'clean_query'   => $intent->cleanQuery !== '' ? $intent->cleanQuery : null,
            'color'         => $intent->color,
            'price_min'     => $intent->priceMin,
            'price_max'     => $intent->priceMax,
            'gender'        => $intent->gender,
            'product_type'  => $intent->productType,
            'category_slug' => $intent->categorySlug,
            'had_intent'    => $intent->hasIntent(),
            'filters'       => $outcome['filters'] ?? null,
            'results_count' => (int) ($outcome['results_count'] ?? 0),
            'relaxed_to'    => $outcome['relaxed_to'] ?? null,
            'channel'       => $outcome['channel'] ?? null,
            'locale'        => $outcome['locale'] ?? null,
            'customer_id'   => $outcome['customer_id'] ?? null,
        ];
    }
}

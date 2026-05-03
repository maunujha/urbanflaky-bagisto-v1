<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\JsonResponse;
use Webkul\Marketing\Repositories\SearchTermRepository;

class TrendingSearchController extends APIController
{
    public function __construct(
        protected SearchTermRepository $searchTermRepository
    ) {}

    public function index(): JsonResponse
    {
        $limit   = max(1, min(20, (int) (core()->getConfigData('catalog.products.search.trending_limit') ?: 8)));
        $channel = core()->getCurrentChannel()->id;
        $locale  = app()->getLocale();

        $terms = $this->searchTermRepository
            ->resetModel()
            ->where('channel_id', $channel)
            ->where('locale', $locale)
            ->where('uses', '>=', 2)
            ->whereRaw('LENGTH(term) >= 2')
            ->orderByDesc('uses')
            ->limit($limit)
            ->get(['term', 'uses'])
            ->map(fn ($t) => ['term' => $t->term, 'count' => $t->uses]);

        return response()->json($terms);
    }
}

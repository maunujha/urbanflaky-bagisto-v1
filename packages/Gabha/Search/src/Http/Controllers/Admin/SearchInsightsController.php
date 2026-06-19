<?php

declare(strict_types=1);

namespace Gabha\Search\Http\Controllers\Admin;

use Gabha\Search\DataGrids\SearchQueryDataGrid;
use Gabha\Search\Models\SearchQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;

/**
 * Admin "Search Insights" page — the visual counterpart to the
 * `urbanflaky:search:analytics` CLI report, over the `search_queries` table.
 *
 * The dashboard (KPI cards + top/zero-result/facet widgets) is computed for a
 * selectable window; the DataGrid below it is the full, independently filterable
 * search log. Read-only apart from a mass-delete used to prune old/test rows.
 */
class SearchInsightsController extends Controller
{
    /**
     * Windows the dashboard can be scoped to (days).
     *
     * @var array<int, int>
     */
    protected array $ranges = [7, 30, 90];

    /**
     * Dashboard + (ajax) datagrid.
     */
    public function index(): mixed
    {
        if (request()->ajax()) {
            return datagrid(SearchQueryDataGrid::class)->process();
        }

        $days = (int) request()->input('range', 30);

        if (! in_array($days, $this->ranges, true)) {
            $days = 30;
        }

        $since = Carbon::now()->subDays($days);

        return view('gabha-search::admin.insights.index', [
            'range'      => $days,
            'ranges'     => $this->ranges,
            'stats'      => $this->stats($since),
            'topTerms'   => $this->topTerms($since),
            'zeroTerms'  => $this->zeroResultTerms($since),
            'facets'     => $this->facetUsage($since),
            'colors'     => $this->distribution($since, 'color'),
            'genders'    => $this->distribution($since, 'gender'),
            'types'      => $this->distribution($since, 'product_type'),
        ]);
    }

    /**
     * Purge selected log rows.
     */
    public function massDelete(MassDestroyRequest $request): JsonResponse
    {
        SearchQuery::whereIn('id', (array) $request->input('indices', []))->delete();

        return new JsonResponse(['message' => 'Selected search records were deleted.']);
    }

    /**
     * Headline KPIs for the window.
     *
     * @return array<string, int>
     */
    protected function stats(Carbon $since): array
    {
        $base = fn (): Builder => SearchQuery::query()->where('created_at', '>=', $since);

        return [
            'total'       => $base()->count(),
            'zero'        => $base()->where('results_count', 0)->count(),
            'with_intent' => $base()->where('had_intent', true)->count(),
            'relaxed'     => $base()->whereNotNull('relaxed_to')->where('relaxed_to', '!=', '')->count(),
        ];
    }

    /**
     * Most frequent terms with their average result count.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    protected function topTerms(Carbon $since)
    {
        return SearchQuery::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('term, COUNT(*) as hits, ROUND(AVG(results_count)) as avg_results')
            ->groupBy('term')
            ->orderByDesc('hits')
            ->limit(10)
            ->get();
    }

    /**
     * Terms that returned nothing — the catalog / synonym gap list.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    protected function zeroResultTerms(Carbon $since)
    {
        return SearchQuery::query()
            ->where('created_at', '>=', $since)
            ->where('results_count', 0)
            ->selectRaw('term, COUNT(*) as hits')
            ->groupBy('term')
            ->orderByDesc('hits')
            ->limit(10)
            ->get();
    }

    /**
     * How many searches expressed each facet of intent.
     *
     * @return array<string, int>
     */
    protected function facetUsage(Carbon $since): array
    {
        $base = fn (): Builder => SearchQuery::query()->where('created_at', '>=', $since);

        return [
            'color'        => $base()->whereNotNull('color')->count(),
            'price'        => $base()->where(fn ($q) => $q->whereNotNull('price_min')->orWhereNotNull('price_max'))->count(),
            'gender'       => $base()->whereNotNull('gender')->count(),
            'product_type' => $base()->whereNotNull('product_type')->count(),
            'category'     => $base()->whereNotNull('category_slug')->count(),
        ];
    }

    /**
     * Top values for a single intent column (e.g. which colours are most asked).
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    protected function distribution(Carbon $since, string $column)
    {
        return SearchQuery::query()
            ->where('created_at', '>=', $since)
            ->whereNotNull($column)
            ->selectRaw("{$column} as label, COUNT(*) as hits")
            ->groupBy($column)
            ->orderByDesc('hits')
            ->limit(8)
            ->get();
    }
}

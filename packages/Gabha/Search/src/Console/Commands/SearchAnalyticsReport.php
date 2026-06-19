<?php

declare(strict_types=1);

namespace Gabha\Search\Console\Commands;

use Gabha\Search\Models\SearchQuery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Merchandising report over the captured natural-language searches.
 *
 *   php artisan urbanflaky:search:analytics                 # last 30 days
 *   php artisan urbanflaky:search:analytics --days=7 --limit=10
 *   php artisan urbanflaky:search:analytics --zero          # only zero-result terms
 *
 * Surfaces the demand signals worth acting on: the most-searched terms, the
 * terms that return nothing (catalog/synonym gaps), how often each facet is used
 * and how often relaxation had to rescue an over-filtered query.
 */
class SearchAnalyticsReport extends Command
{
    protected $signature = 'urbanflaky:search:analytics
                            {--days=30 : How many days back to report on}
                            {--limit=20 : Rows per ranking}
                            {--zero : Show only zero-result terms}';

    protected $description = 'Report on captured natural-language search analytics';

    public function handle(): int
    {
        if ((string) config('gabha-search.analytics.driver', 'database') !== 'database') {
            $this->warn('Analytics driver is not "database" — there is nothing to query. '
                .'Set GABHA_SEARCH_ANALYTICS_DRIVER=database (and migrate) for this report.');

            return self::SUCCESS;
        }

        if (! Schema::hasTable('search_queries')) {
            $this->error('The search_queries table is missing. Run: php artisan migrate');

            return self::FAILURE;
        }

        $days = max(1, (int) $this->option('days'));
        $limit = max(1, (int) $this->option('limit'));
        $since = now()->subDays($days);

        $base = SearchQuery::query()->where('created_at', '>=', $since);

        $total = (clone $base)->count();

        if ($total === 0) {
            $this->info("No searches recorded in the last {$days} day(s).");

            return self::SUCCESS;
        }

        $this->info("Search analytics — last {$days} day(s): {$total} searches recorded.");
        $this->newLine();

        if ($this->option('zero')) {
            $this->renderZeroResult($base, $limit);

            return self::SUCCESS;
        }

        $this->renderTopTerms($base, $limit);
        $this->newLine();
        $this->renderZeroResult($base, $limit);
        $this->newLine();
        $this->renderFacetUsage($base, $total);

        return self::SUCCESS;
    }

    protected function renderTopTerms($base, int $limit): void
    {
        $rows = (clone $base)
            ->selectRaw('term, COUNT(*) as hits, ROUND(AVG(results_count)) as avg_results')
            ->groupBy('term')
            ->orderByDesc('hits')
            ->limit($limit)
            ->get();

        $this->line('<comment>Top searches</comment>');
        $this->table(
            ['Term', 'Searches', 'Avg results'],
            $rows->map(fn ($r) => [$r->term, $r->hits, $r->avg_results])->all()
        );
    }

    protected function renderZeroResult($base, int $limit): void
    {
        $rows = (clone $base)
            ->where('results_count', 0)
            ->selectRaw('term, COUNT(*) as hits')
            ->groupBy('term')
            ->orderByDesc('hits')
            ->limit($limit)
            ->get();

        $this->line('<comment>Zero-result searches</comment> (catalog / synonym gaps to fix)');

        if ($rows->isEmpty()) {
            $this->line('  None — every search returned at least one product.');

            return;
        }

        $this->table(
            ['Term', 'Searches'],
            $rows->map(fn ($r) => [$r->term, $r->hits])->all()
        );
    }

    protected function renderFacetUsage($base, int $total): void
    {
        $count = fn (string $column) => (clone $base)->whereNotNull($column)->count();

        $price = (clone $base)
            ->where(fn ($q) => $q->whereNotNull('price_min')->orWhereNotNull('price_max'))
            ->count();

        $relaxed = (clone $base)->whereNotNull('relaxed_to')->where('relaxed_to', '!=', '')->count();

        $pct = fn (int $n) => $total ? round($n / $total * 100, 1).'%' : '0%';

        $this->line('<comment>Facet usage</comment> (share of searches that expressed each intent)');
        $this->table(
            ['Facet', 'Count', 'Share'],
            [
                ['Colour', $count('color'), $pct($count('color'))],
                ['Price', $price, $pct($price)],
                ['Gender', $count('gender'), $pct($count('gender'))],
                ['Product type', $count('product_type'), $pct($count('product_type'))],
                ['Section', $count('category_slug'), $pct($count('category_slug'))],
                ['Relaxation rescued', $relaxed, $pct($relaxed)],
            ]
        );
    }
}

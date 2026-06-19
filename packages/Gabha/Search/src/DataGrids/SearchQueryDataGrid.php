<?php

declare(strict_types=1);

namespace Gabha\Search\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

/**
 * The raw search log behind the Search Insights page — one row per recorded
 * storefront search, fully sortable / filterable / searchable so a merchant can
 * drill from a summary widget down to the exact queries (and their parsed intent
 * + outcome) that produced it.
 */
class SearchQueryDataGrid extends DataGrid
{
    protected $sortColumn = 'id';

    protected $sortOrder = 'desc';

    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('search_queries')
            ->select(
                'search_queries.id',
                'search_queries.term',
                'search_queries.results_count',
                'search_queries.had_intent',
                'search_queries.color',
                'search_queries.gender',
                'search_queries.product_type',
                'search_queries.price_min',
                'search_queries.price_max',
                'search_queries.relaxed_to',
                'search_queries.created_at',
            );

        $this->addFilter('id', 'search_queries.id');
        $this->addFilter('term', 'search_queries.term');
        $this->addFilter('results_count', 'search_queries.results_count');
        $this->addFilter('gender', 'search_queries.gender');
        $this->addFilter('product_type', 'search_queries.product_type');
        $this->addFilter('color', 'search_queries.color');
        $this->addFilter('relaxed_to', 'search_queries.relaxed_to');

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => 'ID',
            'type'       => 'integer',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'term',
            'label'      => 'Search term',
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'results_count',
            'label'      => 'Results',
            'type'       => 'integer',
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => (int) $row->results_count > 0
                ? '<span class="badge badge-md badge-success">'.(int) $row->results_count.'</span>'
                : '<span class="badge badge-md badge-danger">0</span>',
        ]);

        $this->addColumn([
            'index'      => 'color',
            'label'      => 'Colour',
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->color ?: '—',
        ]);

        $this->addColumn([
            'index'              => 'gender',
            'label'              => 'Gender',
            'type'               => 'string',
            'filterable'         => true,
            'filterable_options' => [
                ['label' => 'Men', 'value' => 'men'],
                ['label' => 'Women', 'value' => 'women'],
            ],
            'sortable'           => true,
            'closure'            => fn ($row) => $row->gender ? ucfirst($row->gender) : '—',
        ]);

        $this->addColumn([
            'index'      => 'product_type',
            'label'      => 'Product type',
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->product_type ?: '—',
        ]);

        $this->addColumn([
            'index'      => 'price_max',
            'label'      => 'Budget',
            'type'       => 'string',
            'sortable'   => false,
            'closure'    => function ($row) {
                $min = $row->price_min !== null ? (int) $row->price_min : null;
                $max = $row->price_max !== null ? (int) $row->price_max : null;

                return match (true) {
                    $min !== null && $max !== null => '₹'.$min.'–₹'.$max,
                    $max !== null                  => '≤ ₹'.$max,
                    $min !== null                  => '≥ ₹'.$min,
                    default                        => '—',
                };
            },
        ]);

        $this->addColumn([
            'index'      => 'relaxed_to',
            'label'      => 'Relaxed',
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->relaxed_to
                ? '<span class="badge badge-md badge-warning">'.$row->relaxed_to.'</span>'
                : '—',
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => 'When',
            'type'       => 'datetime',
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->created_at
                ? core()->formatDate($row->created_at, 'd M Y, h:i A')
                : '—',
        ]);
    }

    public function prepareMassActions(): void
    {
        if (bouncer()->hasPermission('catalog.search_insights.delete')) {
            $this->addMassAction([
                'title'  => 'Delete',
                'method' => 'POST',
                'url'    => route('admin.search.insights.mass_delete'),
            ]);
        }
    }
}

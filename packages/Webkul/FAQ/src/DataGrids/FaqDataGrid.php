<?php

namespace Webkul\FAQ\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class FaqDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('faqs')
            ->select(
                'faqs.id',
                'faqs.question',
                'faqs.sort_order',
                'faqs.status',
                'faq_categories.id as category_id',
                'faq_categories.name as category',
            )
            ->leftJoin('faq_categories', 'faqs.faq_category_id', '=', 'faq_categories.id');

        $this->addFilter('id', 'faqs.id');
        $this->addFilter('question', 'faqs.question');
        $this->addFilter('status', 'faqs.status');
        $this->addFilter('category_id', 'faqs.faq_category_id');
        $this->addFilter('sort_order', 'faqs.sort_order');

        return $queryBuilder;
    }

    /**
     * Add columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('faq::app.admin.faqs.index.datagrid.id'),
            'type'       => 'integer',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'question',
            'label'      => trans('faq::app.admin.faqs.index.datagrid.question'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'              => 'category_id',
            'label'              => trans('faq::app.admin.faqs.index.datagrid.category'),
            'type'               => 'string',
            'filterable'         => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => DB::table('faq_categories')
                ->orderBy('sort_order')
                ->get(['id', 'name'])
                ->map(fn ($category) => ['label' => $category->name, 'value' => $category->id])
                ->toArray(),
            'sortable'           => true,
            'closure'            => fn ($row) => $row->category,
        ]);

        $this->addColumn([
            'index'      => 'sort_order',
            'label'      => trans('faq::app.admin.faqs.index.datagrid.sort-order'),
            'type'       => 'integer',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'              => 'status',
            'label'              => trans('faq::app.admin.faqs.index.datagrid.status'),
            'type'               => 'boolean',
            'filterable'         => true,
            'filterable_options' => [
                [
                    'label' => trans('faq::app.admin.faqs.index.datagrid.active'),
                    'value' => 1,
                ], [
                    'label' => trans('faq::app.admin.faqs.index.datagrid.inactive'),
                    'value' => 0,
                ],
            ],
            'sortable'           => true,
            'closure'            => function ($row) {
                if ($row->status) {
                    return '<span class="badge badge-md badge-success">'.trans('faq::app.admin.faqs.index.datagrid.active').'</span>';
                }

                return '<span class="badge badge-md badge-danger">'.trans('faq::app.admin.faqs.index.datagrid.inactive').'</span>';
            },
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('cms.faqs.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('faq::app.admin.faqs.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => fn ($row) => route('admin.faqs.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('cms.faqs.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('faq::app.admin.faqs.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => fn ($row) => route('admin.faqs.delete', $row->id),
            ]);
        }
    }

    /**
     * Prepare mass actions.
     *
     * @return void
     */
    public function prepareMassActions()
    {
        if (bouncer()->hasPermission('cms.faqs.delete')) {
            $this->addMassAction([
                'title'  => trans('faq::app.admin.faqs.index.datagrid.delete'),
                'method' => 'POST',
                'url'    => route('admin.faqs.mass_delete'),
            ]);
        }
    }
}

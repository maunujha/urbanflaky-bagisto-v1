<?php

namespace Webkul\FAQ\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class FaqCategoryDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('faq_categories')
            ->select(
                'faq_categories.id',
                'faq_categories.name',
                'faq_categories.slug',
                'faq_categories.sort_order',
                'faq_categories.status',
            );

        $this->addFilter('id', 'faq_categories.id');
        $this->addFilter('name', 'faq_categories.name');
        $this->addFilter('status', 'faq_categories.status');
        $this->addFilter('sort_order', 'faq_categories.sort_order');

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
            'label'      => trans('faq::app.admin.categories.index.datagrid.id'),
            'type'       => 'integer',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('faq::app.admin.categories.index.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'slug',
            'label'      => trans('faq::app.admin.categories.index.datagrid.slug'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'sort_order',
            'label'      => trans('faq::app.admin.categories.index.datagrid.sort-order'),
            'type'       => 'integer',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'              => 'status',
            'label'              => trans('faq::app.admin.categories.index.datagrid.status'),
            'type'               => 'boolean',
            'filterable'         => true,
            'filterable_options' => [
                [
                    'label' => trans('faq::app.admin.categories.index.datagrid.active'),
                    'value' => 1,
                ], [
                    'label' => trans('faq::app.admin.categories.index.datagrid.inactive'),
                    'value' => 0,
                ],
            ],
            'sortable'           => true,
            'closure'            => function ($row) {
                if ($row->status) {
                    return '<span class="badge badge-md badge-success">'.trans('faq::app.admin.categories.index.datagrid.active').'</span>';
                }

                return '<span class="badge badge-md badge-danger">'.trans('faq::app.admin.categories.index.datagrid.inactive').'</span>';
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
        if (bouncer()->hasPermission('cms.faq_categories.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('faq::app.admin.categories.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => fn ($row) => route('admin.faqs.categories.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('cms.faq_categories.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('faq::app.admin.categories.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => fn ($row) => route('admin.faqs.categories.delete', $row->id),
            ]);
        }
    }
}

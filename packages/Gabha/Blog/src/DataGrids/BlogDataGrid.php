<?php

namespace Gabha\Blog\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class BlogDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('blogs')
            ->select(
                'blogs.id',
                'blogs.title',
                'blogs.slug',
                'blogs.author',
                'blogs.status',
                'blogs.published_at',
            );

        $this->addFilter('id', 'blogs.id');
        $this->addFilter('title', 'blogs.title');
        $this->addFilter('author', 'blogs.author');
        $this->addFilter('status', 'blogs.status');

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
            'label'      => trans('blog::app.admin.blogs.index.datagrid.id'),
            'type'       => 'integer',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'title',
            'label'      => trans('blog::app.admin.blogs.index.datagrid.title'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'slug',
            'label'      => trans('blog::app.admin.blogs.index.datagrid.slug'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'author',
            'label'      => trans('blog::app.admin.blogs.index.datagrid.author'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'              => 'status',
            'label'              => trans('blog::app.admin.blogs.index.datagrid.status'),
            'type'               => 'boolean',
            'filterable'         => true,
            'filterable_options' => [
                [
                    'label' => trans('blog::app.admin.blogs.index.datagrid.published'),
                    'value' => 1,
                ], [
                    'label' => trans('blog::app.admin.blogs.index.datagrid.draft'),
                    'value' => 0,
                ],
            ],
            'sortable'           => true,
            'closure'            => function ($row) {
                if ($row->status) {
                    return '<span class="badge badge-md badge-success">'.trans('blog::app.admin.blogs.index.datagrid.published').'</span>';
                }

                return '<span class="badge badge-md badge-warning">'.trans('blog::app.admin.blogs.index.datagrid.draft').'</span>';
            },
        ]);

        $this->addColumn([
            'index'      => 'published_at',
            'label'      => trans('blog::app.admin.blogs.index.datagrid.published-at'),
            'type'       => 'datetime',
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->published_at
                ? core()->formatDate($row->published_at, 'd M Y')
                : '—',
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('cms.blogs.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('blog::app.admin.blogs.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => fn ($row) => route('admin.blogs.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('cms.blogs.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('blog::app.admin.blogs.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => fn ($row) => route('admin.blogs.delete', $row->id),
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
        if (bouncer()->hasPermission('cms.blogs.delete')) {
            $this->addMassAction([
                'title'  => trans('blog::app.admin.blogs.index.datagrid.delete'),
                'method' => 'POST',
                'url'    => route('admin.blogs.mass_delete'),
            ]);
        }
    }
}

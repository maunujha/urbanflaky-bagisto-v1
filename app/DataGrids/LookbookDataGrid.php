<?php

namespace App\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DataGrid\DataGrid;

class LookbookDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        return DB::table('lookbook_items')
            ->select(
                'id',
                'title',
                'type',
                'image',
                'collection_name',
                'display_order',
                'is_featured',
                'status'
            );
    }

    /**
     * Add columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('lookbook::app.admin.datagrid.id'),
            'type'       => 'integer',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'          => 'image',
            'label'          => trans('lookbook::app.admin.datagrid.thumbnail'),
            'type'           => 'string',
            'searchable'     => false,
            'filterable'     => false,
            'sortable'       => false,
            'closure'        => function ($row) {
                $url = $row->image ? Storage::url($row->image) : null;

                if (! $url) {
                    return '<span class="text-gray-400">—</span>';
                }

                return '<img src="'.$url.'" class="h-12 w-12 rounded-md object-cover" />';
            },
        ]);

        $this->addColumn([
            'index'      => 'title',
            'label'      => trans('lookbook::app.admin.datagrid.title'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'collection_name',
            'label'      => trans('lookbook::app.admin.datagrid.collection'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'              => 'type',
            'label'              => trans('lookbook::app.admin.datagrid.type'),
            'type'               => 'string',
            'filterable'         => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                ['label' => trans('lookbook::app.admin.datagrid.image'), 'value' => 'image'],
                ['label' => trans('lookbook::app.admin.datagrid.reel'), 'value' => 'reel'],
            ],
            'sortable'           => true,
            'closure'            => function ($row) {
                $label = $row->type === 'reel'
                    ? trans('lookbook::app.admin.datagrid.reel')
                    : trans('lookbook::app.admin.datagrid.image');

                $class = $row->type === 'reel'
                    ? 'label-active'
                    : 'label-info';

                return '<span class="'.$class.'">'.$label.'</span>';
            },
        ]);

        $this->addColumn([
            'index'      => 'display_order',
            'label'      => trans('lookbook::app.admin.datagrid.display-order'),
            'type'       => 'integer',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'              => 'status',
            'label'              => trans('lookbook::app.admin.datagrid.status'),
            'type'               => 'boolean',
            'filterable'         => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                ['label' => trans('lookbook::app.admin.datagrid.active'), 'value' => 1],
                ['label' => trans('lookbook::app.admin.datagrid.inactive'), 'value' => 0],
            ],
            'sortable'           => true,
            'closure'            => function ($row) {
                if ($row->status) {
                    return '<span class="label-active">'.trans('lookbook::app.admin.datagrid.active').'</span>';
                }

                return '<span class="label-pending">'.trans('lookbook::app.admin.datagrid.inactive').'</span>';
            },
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        $this->addAction([
            'index'  => 'edit',
            'icon'   => 'icon-edit',
            'title'  => trans('lookbook::app.admin.datagrid.edit'),
            'method' => 'GET',
            'url'    => fn ($row) => route('admin.lookbook.edit', $row->id),
        ]);

        $this->addAction([
            'index'  => 'delete',
            'icon'   => 'icon-delete',
            'title'  => trans('lookbook::app.admin.datagrid.delete'),
            'method' => 'DELETE',
            'url'    => fn ($row) => route('admin.lookbook.delete', $row->id),
        ]);
    }

    /**
     * Prepare mass actions.
     */
    public function prepareMassActions(): void
    {
        $this->addMassAction([
            'title'   => trans('lookbook::app.admin.datagrid.delete'),
            'url'     => route('admin.lookbook.mass_delete'),
            'method'  => 'POST',
        ]);

        $this->addMassAction([
            'title'   => trans('lookbook::app.admin.datagrid.update-status'),
            'url'     => route('admin.lookbook.mass_update'),
            'method'  => 'POST',
            'options' => [
                ['label' => trans('lookbook::app.admin.datagrid.active'), 'value' => 1],
                ['label' => trans('lookbook::app.admin.datagrid.inactive'), 'value' => 0],
            ],
        ]);
    }
}

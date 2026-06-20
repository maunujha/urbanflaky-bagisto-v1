<?php

declare(strict_types=1);

namespace Gabha\Inventory\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class PurchaseDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('purchases')
            ->leftJoin('vendors', 'purchases.vendor_id', '=', 'vendors.id')
            ->select(
                'purchases.id',
                'purchases.purchase_number',
                'vendors.name as vendor_name',
                'purchases.purchase_date',
                'purchases.invoice_number',
                'purchases.total_quantity',
                'purchases.total_amount',
                'purchases.created_at',
            );

        $this->addFilter('id', 'purchases.id');
        $this->addFilter('purchase_number', 'purchases.purchase_number');
        $this->addFilter('vendor_name', 'vendors.name');
        $this->addFilter('invoice_number', 'purchases.invoice_number');
        $this->addFilter('purchase_date', 'purchases.purchase_date');
        $this->addFilter('total_quantity', 'purchases.total_quantity');
        /* Qualify created_at — both purchases and vendors expose it (join). */
        $this->addFilter('created_at', 'purchases.created_at');

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
            'label'      => trans('inventory::app.admin.purchases.index.datagrid.id'),
            'type'       => 'integer',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'purchase_number',
            'label'      => trans('inventory::app.admin.purchases.index.datagrid.purchase-number'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'vendor_name',
            'label'      => trans('inventory::app.admin.purchases.index.datagrid.vendor'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'purchase_date',
            'label'      => trans('inventory::app.admin.purchases.index.datagrid.purchase-date'),
            'type'       => 'date',
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => core()->formatDate($row->purchase_date, 'd M Y'),
        ]);

        $this->addColumn([
            'index'      => 'invoice_number',
            'label'      => trans('inventory::app.admin.purchases.index.datagrid.invoice-number'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->invoice_number ?: '—',
        ]);

        $this->addColumn([
            'index'      => 'total_quantity',
            'label'      => trans('inventory::app.admin.purchases.index.datagrid.total-quantity'),
            'type'       => 'integer',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'total_amount',
            'label'      => trans('inventory::app.admin.purchases.index.datagrid.total-amount'),
            'type'       => 'string',
            'sortable'   => true,
            'closure'    => fn ($row) => core()->formatBasePrice($row->total_amount),
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => trans('inventory::app.admin.purchases.index.datagrid.created-at'),
            'type'       => 'datetime',
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => core()->formatDate($row->created_at, 'd M Y'),
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('inventory.purchases.view')) {
            $this->addAction([
                'icon'   => 'icon-view',
                'title'  => trans('inventory::app.admin.purchases.index.datagrid.view'),
                'method' => 'GET',
                'url'    => fn ($row) => route('admin.inventory.purchases.view', $row->id),
            ]);
        }
    }
}

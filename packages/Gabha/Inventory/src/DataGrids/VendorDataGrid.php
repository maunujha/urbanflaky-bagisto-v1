<?php

declare(strict_types=1);

namespace Gabha\Inventory\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Webkul\DataGrid\DataGrid;

class VendorDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('vendors')
            ->select(
                'vendors.id',
                'vendors.name',
                'vendors.mobile',
                'vendors.address',
                'vendors.created_at',
            );

        /*
         * "Total purchases" and "Last purchase date" are derived from the
         * (separately-owned) `purchases` table. They are resolved with
         * correlated sub-queries only when that table exists, so the vendor
         * grid works standalone today and lights up automatically once the
         * Purchase module is installed — no join, no missing-table errors.
         */
        if (Schema::hasTable('purchases')) {
            $queryBuilder
                ->selectSub(
                    DB::table('purchases')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn('purchases.vendor_id', 'vendors.id'),
                    'total_purchases'
                )
                ->selectSub(
                    DB::table('purchases')
                        ->selectRaw('MAX(purchases.created_at)')
                        ->whereColumn('purchases.vendor_id', 'vendors.id'),
                    'last_purchase_date'
                );
        } else {
            $queryBuilder
                ->selectRaw('0 as total_purchases')
                ->selectRaw('NULL as last_purchase_date');
        }

        $this->addFilter('id', 'vendors.id');
        $this->addFilter('name', 'vendors.name');
        $this->addFilter('mobile', 'vendors.mobile');
        $this->addFilter('created_at', 'vendors.created_at');

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
            'label'      => trans('inventory::app.admin.vendors.index.datagrid.id'),
            'type'       => 'integer',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('inventory::app.admin.vendors.index.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'mobile',
            'label'      => trans('inventory::app.admin.vendors.index.datagrid.mobile'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'address',
            'label'      => trans('inventory::app.admin.vendors.index.datagrid.address'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => fn ($row) => e(Str::limit((string) $row->address, 60)),
        ]);

        $this->addColumn([
            'index'      => 'total_purchases',
            'label'      => trans('inventory::app.admin.vendors.index.datagrid.total-purchases'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => fn ($row) => (int) ($row->total_purchases ?? 0),
        ]);

        $this->addColumn([
            'index'      => 'last_purchase_date',
            'label'      => trans('inventory::app.admin.vendors.index.datagrid.last-purchase-date'),
            'type'       => 'datetime',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => fn ($row) => $row->last_purchase_date
                ? core()->formatDate($row->last_purchase_date, 'd M Y')
                : '—',
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => trans('inventory::app.admin.vendors.index.datagrid.created-at'),
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
        if (bouncer()->hasPermission('inventory.vendors.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('inventory::app.admin.vendors.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => fn ($row) => route('admin.inventory.vendors.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('inventory.vendors.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('inventory::app.admin.vendors.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => fn ($row) => route('admin.inventory.vendors.delete', $row->id),
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
        if (bouncer()->hasPermission('inventory.vendors.delete')) {
            $this->addMassAction([
                'title'  => trans('inventory::app.admin.vendors.index.datagrid.delete'),
                'method' => 'POST',
                'url'    => route('admin.inventory.vendors.mass_delete'),
            ]);
        }
    }
}

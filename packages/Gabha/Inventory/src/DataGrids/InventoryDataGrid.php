<?php

declare(strict_types=1);

namespace Gabha\Inventory\DataGrids;

use Gabha\Inventory\Repositories\VendorRepository;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class InventoryDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $prefix = DB::getTablePrefix();
        $locale = app()->getLocale();
        $threshold = (int) config('inventory.low_stock_threshold', 10);

        $colorId = (int) DB::table('attributes')->where('code', 'color')->value('id');
        $sizeId = (int) DB::table('attributes')->where('code', 'size')->value('id');

        /*
         * A variant's "vendor" is the vendor of its most recent purchase. The
         * inventories table has no vendor_id (a variant can be restocked from
         * different vendors over time, hence the blended average_cost), so
         * this scalar subquery picks the latest one for filtering/display.
         */
        $latestVendorSubquery = '(
            select '.$prefix.'purchases.vendor_id
            from '.$prefix.'stock_movements
            inner join '.$prefix.'purchases on '.$prefix.'purchases.id = '.$prefix.'stock_movements.reference_id
                and '.$prefix.'stock_movements.reference_type = \'purchase\'
            where '.$prefix.'stock_movements.product_variant_id = '.$prefix.'inventories.product_variant_id
            order by '.$prefix.'stock_movements.created_at desc
            limit 1
        )';

        $queryBuilder = DB::table('inventories')
            ->leftJoin('products', 'products.id', '=', 'inventories.product_variant_id')
            ->leftJoin('product_flat as self_flat', function ($join) use ($locale) {
                $join->on('self_flat.product_id', '=', 'inventories.product_variant_id')
                    ->where('self_flat.locale', $locale);
            })
            ->leftJoin('product_flat as parent_flat', function ($join) use ($locale) {
                $join->on('parent_flat.product_id', '=', 'products.parent_id')
                    ->where('parent_flat.locale', $locale);
            })
            ->leftJoin('product_attribute_values as color_pav', function ($join) use ($colorId) {
                $join->on('color_pav.product_id', '=', 'inventories.product_variant_id')
                    ->where('color_pav.attribute_id', $colorId);
            })
            ->leftJoin('attribute_options as color_opt', 'color_opt.id', '=', 'color_pav.integer_value')
            ->leftJoin('product_attribute_values as size_pav', function ($join) use ($sizeId) {
                $join->on('size_pav.product_id', '=', 'inventories.product_variant_id')
                    ->where('size_pav.attribute_id', $sizeId);
            })
            ->leftJoin('attribute_options as size_opt', 'size_opt.id', '=', 'size_pav.integer_value')
            ->leftJoin('vendors', 'vendors.id', '=', DB::raw($latestVendorSubquery))
            ->select(
                'inventories.id',
                'inventories.product_variant_id',
                'inventories.current_stock',
                'inventories.average_cost',
                'inventories.inventory_value',
                'products.sku',
                DB::raw('COALESCE(parent_flat.name, self_flat.name) as product_name'),
                DB::raw('color_opt.admin_name as color'),
                DB::raw('size_opt.admin_name as size'),
                DB::raw('('.$prefix.'inventories.current_stock <= '.$threshold.') as is_low_stock'),
                DB::raw('vendors.name as vendor_name'),
            );

        /*
         * Map each searchable / filterable column to a real (qualified) column
         * or expression so WHERE clauses don't reference SELECT aliases.
         */
        $this->addFilter('product_name', DB::raw('COALESCE('.$prefix.'parent_flat.name, '.$prefix.'self_flat.name)'));
        $this->addFilter('sku', 'products.sku');
        $this->addFilter('color', 'color_opt.admin_name');
        $this->addFilter('size', 'size_opt.admin_name');
        $this->addFilter('current_stock', 'inventories.current_stock');
        $this->addFilter('inventory_value', 'inventories.inventory_value');
        $this->addFilter('is_low_stock', DB::raw('('.$prefix.'inventories.current_stock <= '.$threshold.')'));
        $this->addFilter('vendor_name', DB::raw($latestVendorSubquery));

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
            'index'      => 'product_name',
            'label'      => trans('inventory::app.admin.stock.index.datagrid.product'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'              => 'vendor_name',
            'label'              => trans('inventory::app.admin.stock.index.datagrid.vendor'),
            'type'               => 'string',
            'filterable'         => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => app(VendorRepository::class)->all()
                ->sortBy('name')
                ->map(fn ($vendor) => ['label' => $vendor->name, 'value' => (string) $vendor->id])
                ->values()
                ->all(),
            'sortable'           => false,
            'closure'            => fn ($row) => $row->vendor_name ?: '—',
        ]);

        $this->addColumn([
            'index'      => 'sku',
            'label'      => trans('inventory::app.admin.stock.index.datagrid.sku'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'color',
            'label'      => trans('inventory::app.admin.stock.index.datagrid.color'),
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => false,
            'closure'    => fn ($row) => $row->color ?: '—',
        ]);

        $this->addColumn([
            'index'      => 'size',
            'label'      => trans('inventory::app.admin.stock.index.datagrid.size'),
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => false,
            'closure'    => fn ($row) => $row->size ?: '—',
        ]);

        $this->addColumn([
            'index'      => 'current_stock',
            'label'      => trans('inventory::app.admin.stock.index.datagrid.current-stock'),
            'type'       => 'integer',
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'average_cost',
            'label'      => trans('inventory::app.admin.stock.index.datagrid.average-cost'),
            'type'       => 'string',
            'sortable'   => true,
            'closure'    => fn ($row) => core()->formatBasePrice((float) $row->average_cost),
        ]);

        $this->addColumn([
            'index'      => 'inventory_value',
            'label'      => trans('inventory::app.admin.stock.index.datagrid.inventory-value'),
            'type'       => 'string',
            'sortable'   => true,
            'closure'    => fn ($row) => core()->formatBasePrice((float) $row->inventory_value),
        ]);

        $this->addColumn([
            'index'              => 'is_low_stock',
            'label'              => trans('inventory::app.admin.stock.index.datagrid.low-stock'),
            'type'               => 'boolean',
            'filterable'         => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                [
                    'label' => trans('inventory::app.admin.stock.index.datagrid.low'),
                    'value' => 1,
                ], [
                    'label' => trans('inventory::app.admin.stock.index.datagrid.in-stock'),
                    'value' => 0,
                ],
            ],
            'sortable'           => false,
            'closure'            => function ($row) {
                if ($row->is_low_stock) {
                    return '<span class="badge badge-md badge-warning">'.trans('inventory::app.admin.stock.index.datagrid.low').'</span>';
                }

                return '<span class="badge badge-md badge-success">'.trans('inventory::app.admin.stock.index.datagrid.in-stock').'</span>';
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
        if (bouncer()->hasPermission('catalog.products.edit')) {
            $this->addAction([
                'icon'   => 'icon-view',
                'title'  => trans('inventory::app.admin.stock.index.datagrid.view-product'),
                'method' => 'GET',
                'url'    => fn ($row) => route('admin.catalog.products.edit', $row->product_variant_id),
            ]);
        }
    }
}

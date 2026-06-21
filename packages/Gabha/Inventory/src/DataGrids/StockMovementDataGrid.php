<?php

declare(strict_types=1);

namespace Gabha\Inventory\DataGrids;

use Gabha\Inventory\Enums\MovementType;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class StockMovementDataGrid extends DataGrid
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

        $queryBuilder = DB::table('stock_movements')
            ->leftJoin('products', 'products.id', '=', 'stock_movements.product_variant_id')
            ->leftJoin('product_flat as self_flat', function ($join) use ($locale) {
                $join->on('self_flat.product_id', '=', 'stock_movements.product_variant_id')
                    ->where('self_flat.locale', $locale);
            })
            ->leftJoin('product_flat as parent_flat', function ($join) use ($locale) {
                $join->on('parent_flat.product_id', '=', 'products.parent_id')
                    ->where('parent_flat.locale', $locale);
            })
            ->leftJoin('purchases', function ($join) {
                $join->on('purchases.id', '=', 'stock_movements.reference_id')
                    ->where('stock_movements.reference_type', '=', 'purchase');
            })
            ->select(
                'stock_movements.id',
                'stock_movements.movement_number',
                'stock_movements.product_variant_id',
                'stock_movements.movement_type',
                'stock_movements.quantity',
                'stock_movements.qty_before',
                'stock_movements.qty_after',
                'stock_movements.notes',
                'stock_movements.created_at',
                'products.sku',
                DB::raw('COALESCE(parent_flat.name, self_flat.name) as product_name'),
                DB::raw('COALESCE('.$prefix.'purchases.purchase_number, "—") as reference_number'),
            );

        $this->addFilter('product_name', DB::raw('COALESCE('.$prefix.'parent_flat.name, '.$prefix.'self_flat.name)'));
        $this->addFilter('sku', 'products.sku');
        $this->addFilter('movement_type', 'stock_movements.movement_type');
        $this->addFilter('created_at', 'stock_movements.created_at');
        $this->addFilter('movement_number', 'stock_movements.movement_number');

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
            'index'      => 'created_at',
            'label'      => trans('inventory::app.admin.movements.index.datagrid.date'),
            'type'       => 'datetime',
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => core()->formatDate($row->created_at, 'd M Y, h:i A'),
        ]);

        $this->addColumn([
            'index'      => 'movement_number',
            'label'      => trans('inventory::app.admin.movements.index.datagrid.movement-number'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'product_name',
            'label'      => trans('inventory::app.admin.movements.index.datagrid.product'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => false,
            'closure'    => fn ($row) => $row->product_name ?: trans('inventory::app.admin.movements.index.datagrid.deleted-variant'),
        ]);

        $this->addColumn([
            'index'      => 'sku',
            'label'      => trans('inventory::app.admin.movements.index.datagrid.sku'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'quantity',
            'label'      => trans('inventory::app.admin.movements.index.datagrid.quantity'),
            'type'       => 'integer',
            'sortable'   => true,
            'closure'    => function ($row) {
                $sign = MovementType::from($row->movement_type)->isInbound() ? '+' : '−';

                return $sign.$row->quantity;
            },
        ]);

        $this->addColumn([
            'index'              => 'movement_type',
            'label'              => trans('inventory::app.admin.movements.index.datagrid.movement-type'),
            'type'               => 'string',
            'filterable'         => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => collect(MovementType::cases())
                ->map(fn (MovementType $type) => ['label' => $type->label(), 'value' => $type->value])
                ->all(),
            'sortable'           => false,
            'closure'            => function ($row) {
                $type = MovementType::from($row->movement_type);

                $class = $type->isInbound() ? 'badge-success' : 'badge-warning';

                return '<span class="badge badge-md '.$class.'">'.$type->label().'</span>';
            },
        ]);

        $this->addColumn([
            'index'      => 'qty_before',
            'label'      => trans('inventory::app.admin.movements.index.datagrid.previous-stock'),
            'type'       => 'integer',
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'      => 'qty_after',
            'label'      => trans('inventory::app.admin.movements.index.datagrid.new-stock'),
            'type'       => 'integer',
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'      => 'reference_number',
            'label'      => trans('inventory::app.admin.movements.index.datagrid.reference-number'),
            'type'       => 'string',
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'      => 'notes',
            'label'      => trans('inventory::app.admin.movements.index.datagrid.notes'),
            'type'       => 'string',
            'sortable'   => false,
            'closure'    => fn ($row) => $row->notes ?: '—',
        ]);
    }
}

<?php

namespace Gabha\Inventory\Http\Controllers\Admin;

use Gabha\Inventory\DataGrids\InventoryDataGrid;
use Gabha\Inventory\Repositories\InventoryRepository;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;

/**
 * Module 3 — the inventory list and dashboard cards.
 */
class InventoryController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected InventoryRepository $inventoryRepository) {}

    /**
     * Display the inventory list with the dashboard summary cards.
     */
    public function index(): mixed
    {
        if (request()->ajax()) {
            return datagrid(InventoryDataGrid::class)->process();
        }

        $stats = $this->inventoryRepository->dashboardStats();

        return view('inventory::admin.stock.index', compact('stats'));
    }
}

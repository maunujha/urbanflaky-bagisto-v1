<?php

namespace Gabha\Inventory\Http\Controllers\Admin;

use Gabha\Inventory\DataGrids\StockMovementDataGrid;
use Gabha\Inventory\Enums\MovementType;
use Gabha\Inventory\Exceptions\NegativeInventoryException;
use Gabha\Inventory\Http\Requests\StockMovementStoreRequest;
use Gabha\Inventory\Services\StockMovementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;

/**
 * Module 4 — manual stock movements and the movement history.
 */
class StockMovementController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected StockMovementService $stockMovementService) {}

    /**
     * Display the stock movement history.
     */
    public function index(): mixed
    {
        if (request()->ajax()) {
            return datagrid(StockMovementDataGrid::class)->process();
        }

        return view('inventory::admin.movements.index');
    }

    /**
     * Show the manual stock movement form.
     */
    public function create(): View
    {
        $movementTypes = MovementType::manualCases();

        return view('inventory::admin.movements.create', compact('movementTypes'));
    }

    /**
     * Record a manual stock movement, rejecting any that would oversell.
     */
    public function store(StockMovementStoreRequest $request): RedirectResponse
    {
        try {
            $movement = $this->stockMovementService->record([
                'product_variant_id' => $request->integer('product_variant_id'),
                'movement_type'      => $request->input('movement_type'),
                'quantity'           => $request->integer('quantity'),
                'notes'              => $request->input('notes'),
            ]);
        } catch (NegativeInventoryException $e) {
            return back()
                ->withInput()
                ->withErrors(['quantity' => $e->getMessage()]);
        }

        session()->flash('success', trans('inventory::app.admin.movements.create-success', [
            'number' => $movement->movement_number,
        ]));

        return redirect()->route('admin.inventory.movements.index');
    }
}

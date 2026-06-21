<?php

namespace Gabha\Inventory\Http\Controllers\Admin;

use Gabha\Inventory\DataGrids\PurchaseDataGrid;
use Gabha\Inventory\Http\Requests\PurchaseAddItemsRequest;
use Gabha\Inventory\Http\Requests\PurchaseStoreRequest;
use Gabha\Inventory\Repositories\PurchaseRepository;
use Gabha\Inventory\Repositories\VendorRepository;
use Gabha\Inventory\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Admin\Http\Controllers\Controller;

class PurchaseController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected PurchaseRepository $purchaseRepository,
        protected VendorRepository $vendorRepository,
        protected PurchaseService $purchaseService,
    ) {}

    /**
     * Display a listing of the purchases.
     */
    public function index(): mixed
    {
        if (request()->ajax()) {
            return datagrid(PurchaseDataGrid::class)->process();
        }

        return view('inventory::admin.purchases.index');
    }

    /**
     * Show the multi-step purchase creation form.
     */
    public function create(): View
    {
        $vendors = $this->vendorRepository->all()->sortBy('name')->values();

        return view('inventory::admin.purchases.create', compact('vendors'));
    }

    /**
     * Store a newly created purchase (atomic: records + stock + inventory).
     */
    public function store(PurchaseStoreRequest $request): RedirectResponse
    {
        $purchase = $this->purchaseService->create(
            $request->validated(),
            $request->file('bill_file')
        );

        session()->flash('success', trans('inventory::app.admin.purchases.create-success', [
            'number' => $purchase->purchase_number,
        ]));

        return redirect()->route('admin.inventory.purchases.index');
    }

    /**
     * Display a single purchase with its line items.
     */
    public function show(int $id): View
    {
        $purchase = $this->purchaseRepository
            ->with(['vendor', 'items.variant.parent.super_attributes.options'])
            ->findOrFail($id);

        return view('inventory::admin.purchases.view', compact('purchase'));
    }

    /**
     * Show the form to append new line items to an existing purchase.
     */
    public function addItems(int $id): View
    {
        $purchase = $this->purchaseRepository
            ->with('vendor')
            ->findOrFail($id);

        return view('inventory::admin.purchases.add-items', compact('purchase'));
    }

    /**
     * Persist the new line items onto an existing purchase.
     */
    public function storeAddItems(PurchaseAddItemsRequest $request, int $id): RedirectResponse
    {
        $purchase = $this->purchaseRepository->findOrFail($id);

        $this->purchaseService->addItems($purchase, $request->validated()['items']);

        session()->flash('success', trans('inventory::app.admin.purchases.add-items.success', [
            'number' => $purchase->purchase_number,
        ]));

        return redirect()->route('admin.inventory.purchases.view', $purchase->id);
    }

    /**
     * Stream the stored bill document for an authenticated admin.
     */
    public function downloadBill(int $id): StreamedResponse
    {
        $purchase = $this->purchaseRepository->findOrFail($id);

        if (! $purchase->bill_file || ! Storage::exists($purchase->bill_file)) {
            abort(404);
        }

        return Storage::download($purchase->bill_file);
    }
}

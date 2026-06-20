<?php

namespace Gabha\Inventory\Http\Controllers\Admin;

use Gabha\Inventory\DataGrids\VendorDataGrid;
use Gabha\Inventory\Exceptions\VendorHasPurchasesException;
use Gabha\Inventory\Http\Requests\VendorStoreRequest;
use Gabha\Inventory\Http\Requests\VendorUpdateRequest;
use Gabha\Inventory\Repositories\VendorRepository;
use Gabha\Inventory\Services\VendorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;

/**
 * Admin CRUD for vendors.
 *
 * Deliberately thin: validation lives in the form requests, persistence in the
 * repository and all business rules / transactions in {@see VendorService}.
 */
class VendorController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected VendorRepository $vendorRepository,
        protected VendorService $vendorService,
    ) {}

    /**
     * Display a listing of the vendors.
     */
    public function index(): mixed
    {
        if (request()->ajax()) {
            return datagrid(VendorDataGrid::class)->process();
        }

        return view('inventory::admin.vendors.index');
    }

    /**
     * Show the form for creating a new vendor.
     */
    public function create(): View
    {
        return view('inventory::admin.vendors.create');
    }

    /**
     * Store a newly created vendor in storage.
     */
    public function store(VendorStoreRequest $request): RedirectResponse
    {
        $this->vendorService->create($request->validated());

        session()->flash('success', trans('inventory::app.admin.vendors.create-success'));

        return redirect()->route('admin.inventory.vendors.index');
    }

    /**
     * Show the form for editing the specified vendor.
     */
    public function edit(int $id): View
    {
        $vendor = $this->vendorRepository->findOrFail($id);

        return view('inventory::admin.vendors.edit', compact('vendor'));
    }

    /**
     * Update the specified vendor in storage.
     */
    public function update(VendorUpdateRequest $request, int $id): RedirectResponse
    {
        $this->vendorRepository->findOrFail($id);

        $this->vendorService->update($request->validated(), $id);

        session()->flash('success', trans('inventory::app.admin.vendors.update-success'));

        return redirect()->route('admin.inventory.vendors.index');
    }

    /**
     * Remove the specified vendor from storage.
     *
     * Deletion is blocked while purchase records reference the vendor.
     */
    public function delete(int $id): JsonResponse
    {
        $this->vendorRepository->findOrFail($id);

        try {
            $this->vendorService->delete($id);

            return new JsonResponse([
                'message' => trans('inventory::app.admin.vendors.delete-success'),
            ]);
        } catch (VendorHasPurchasesException $e) {
            return new JsonResponse([
                'message' => trans('inventory::app.admin.vendors.delete-has-purchases'),
            ], 422);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => trans('inventory::app.admin.vendors.delete-failed'),
            ], 500);
        }
    }

    /**
     * Mass delete vendors, skipping any that still have purchase records.
     */
    public function massDelete(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $indices = $massDestroyRequest->input('indices', []);

        $blocked = 0;

        foreach ($indices as $index) {
            try {
                $this->vendorService->delete((int) $index);
            } catch (VendorHasPurchasesException $e) {
                $blocked++;
            }
        }

        if ($blocked > 0) {
            return new JsonResponse([
                'message' => trans('inventory::app.admin.vendors.mass-delete-partial', ['blocked' => $blocked]),
            ]);
        }

        return new JsonResponse([
            'message' => trans('inventory::app.admin.vendors.mass-delete-success'),
        ]);
    }
}

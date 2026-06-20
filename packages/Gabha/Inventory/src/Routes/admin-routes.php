<?php

use Gabha\Inventory\Http\Controllers\Admin\InventoryController;
use Gabha\Inventory\Http\Controllers\Admin\PurchaseController;
use Gabha\Inventory\Http\Controllers\Admin\StockMovementController;
use Gabha\Inventory\Http\Controllers\Admin\VariantController;
use Gabha\Inventory\Http\Controllers\Admin\VendorController;
use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;

Route::group(['middleware' => ['web', 'admin', NoCacheMiddleware::class], 'prefix' => config('app.admin_url')], function () {
    /**
     * Vendor routes.
     */
    Route::controller(VendorController::class)->prefix('inventory/vendors')->group(function () {
        Route::get('', 'index')->name('admin.inventory.vendors.index');

        Route::get('create', 'create')->name('admin.inventory.vendors.create');

        Route::post('create', 'store')->name('admin.inventory.vendors.store');

        Route::get('edit/{id}', 'edit')->name('admin.inventory.vendors.edit');

        Route::put('edit/{id}', 'update')->name('admin.inventory.vendors.update');

        Route::delete('delete/{id}', 'delete')->name('admin.inventory.vendors.delete');

        Route::post('mass-delete', 'massDelete')->name('admin.inventory.vendors.mass_delete');
    });

    /**
     * Purchase routes.
     */
    Route::controller(PurchaseController::class)->prefix('inventory/purchases')->group(function () {
        Route::get('', 'index')->name('admin.inventory.purchases.index');

        Route::get('create', 'create')->name('admin.inventory.purchases.create');

        Route::post('create', 'store')->name('admin.inventory.purchases.store');

        Route::get('variants/search', 'searchVariants')->name('admin.inventory.purchases.variants.search');

        Route::get('view/{id}', 'show')->name('admin.inventory.purchases.view');

        Route::get('view/{id}/bill', 'downloadBill')->name('admin.inventory.purchases.bill');
    });

    /**
     * Shared product-variant autocomplete (used by purchase + movement create).
     */
    Route::get('inventory/variants/search', [VariantController::class, 'search'])
        ->name('admin.inventory.variants.search');

    /**
     * Inventory list + dashboard (Module 3).
     */
    Route::controller(InventoryController::class)->prefix('inventory/stock')->group(function () {
        Route::get('', 'index')->name('admin.inventory.stock.index');
    });

    /**
     * Stock movements: history + manual create (Module 4).
     */
    Route::controller(StockMovementController::class)->prefix('inventory/movements')->group(function () {
        Route::get('', 'index')->name('admin.inventory.movements.index');

        Route::get('create', 'create')->name('admin.inventory.movements.create');

        Route::post('create', 'store')->name('admin.inventory.movements.store');
    });
});

<?php

use App\Http\Controllers\Admin\ShiprocketShipmentController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['web', 'admin'],
    'prefix'     => config('app.admin_url').'/sales/orders/{orderId}/shiprocket',
], function () {
    Route::controller(ShiprocketShipmentController::class)->group(function () {
        Route::post('push', 'push')->name('admin.sales.orders.shiprocket.push');

        Route::get('couriers', 'couriers')->name('admin.sales.orders.shiprocket.couriers');

        Route::post('assign', 'assign')->name('admin.sales.orders.shiprocket.assign');

        Route::post('pickup', 'pickup')->name('admin.sales.orders.shiprocket.pickup');

        Route::post('refresh', 'refresh')->name('admin.sales.orders.shiprocket.refresh');

        Route::post('cancel', 'cancel')->name('admin.sales.orders.shiprocket.cancel');

        Route::get('document/{type}', 'document')
            ->whereIn('type', ['label', 'manifest', 'invoice'])
            ->name('admin.sales.orders.shiprocket.document');
    });
});

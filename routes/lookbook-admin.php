<?php

use App\Http\Controllers\Admin\LookbookController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['web', 'admin'],
    'prefix'     => config('app.admin_url').'/lookbook',
], function () {
    Route::controller(LookbookController::class)->group(function () {
        Route::get('', 'index')->name('admin.lookbook.index');

        Route::get('create', 'create')->name('admin.lookbook.create');

        Route::post('create', 'store')->name('admin.lookbook.store');

        Route::get('edit/{id}', 'edit')->name('admin.lookbook.edit');

        Route::put('edit/{id}', 'update')->name('admin.lookbook.update');

        Route::delete('delete/{id}', 'destroy')->name('admin.lookbook.delete');

        Route::post('mass-delete', 'massDestroy')->name('admin.lookbook.mass_delete');

        Route::post('mass-update', 'massUpdate')->name('admin.lookbook.mass_update');

        Route::get('search-products', 'searchProducts')->name('admin.lookbook.search_products');
    });
});

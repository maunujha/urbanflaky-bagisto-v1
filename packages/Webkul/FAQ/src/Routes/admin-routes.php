<?php

use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;
use Webkul\FAQ\Http\Controllers\Admin\FaqCategoryController;
use Webkul\FAQ\Http\Controllers\Admin\FaqController;

Route::group(['middleware' => ['web', 'admin', NoCacheMiddleware::class], 'prefix' => config('app.admin_url')], function () {
    /**
     * FAQ routes.
     */
    Route::controller(FaqController::class)->prefix('faqs')->group(function () {
        Route::get('', 'index')->name('admin.faqs.index');

        Route::get('create', 'create')->name('admin.faqs.create');

        Route::post('create', 'store')->name('admin.faqs.store');

        Route::get('edit/{id}', 'edit')->name('admin.faqs.edit');

        Route::put('edit/{id}', 'update')->name('admin.faqs.update');

        Route::delete('delete/{id}', 'delete')->name('admin.faqs.delete');

        Route::post('mass-delete', 'massDelete')->name('admin.faqs.mass_delete');
    });

    /**
     * FAQ category routes.
     */
    Route::controller(FaqCategoryController::class)->prefix('faqs/categories')->group(function () {
        Route::get('', 'index')->name('admin.faqs.categories.index');

        Route::get('create', 'create')->name('admin.faqs.categories.create');

        Route::post('create', 'store')->name('admin.faqs.categories.store');

        Route::get('edit/{id}', 'edit')->name('admin.faqs.categories.edit');

        Route::put('edit/{id}', 'update')->name('admin.faqs.categories.update');

        Route::delete('delete/{id}', 'delete')->name('admin.faqs.categories.delete');
    });
});

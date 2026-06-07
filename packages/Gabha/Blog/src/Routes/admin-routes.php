<?php

use Gabha\Blog\Http\Controllers\Admin\BlogController;
use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;

Route::group(['middleware' => ['web', 'admin', NoCacheMiddleware::class], 'prefix' => config('app.admin_url')], function () {
    /**
     * Blog routes.
     */
    Route::controller(BlogController::class)->prefix('blogs')->group(function () {
        Route::get('', 'index')->name('admin.blogs.index');

        Route::get('create', 'create')->name('admin.blogs.create');

        Route::post('create', 'store')->name('admin.blogs.store');

        Route::get('edit/{id}', 'edit')->name('admin.blogs.edit');

        Route::put('edit/{id}', 'update')->name('admin.blogs.update');

        Route::delete('delete/{id}', 'delete')->name('admin.blogs.delete');

        Route::post('mass-delete', 'massDelete')->name('admin.blogs.mass_delete');
    });
});

<?php

use Gabha\Notifications\Http\Controllers\Admin\EmailLogController;
use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;

Route::group(['middleware' => ['web', 'admin', NoCacheMiddleware::class], 'prefix' => config('app.admin_url')], function () {
    Route::controller(EmailLogController::class)->prefix('marketing/communications/email-log')->group(function () {
        Route::get('', 'index')->name('admin.notifications.logs.index');

        Route::get('preview/{type}', 'preview')->name('admin.notifications.logs.preview');
    });
});

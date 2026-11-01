<?php

use Gabha\Notifications\Http\Controllers\Admin\EmailLogController;
use Gabha\Notifications\Http\Controllers\Admin\EmailTemplateController;
use Gabha\Notifications\Http\Controllers\Admin\FeedbackController;
use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;

Route::group(['middleware' => ['web', 'admin', NoCacheMiddleware::class], 'prefix' => config('app.admin_url')], function () {
    Route::get('marketing/communications/email-log', [EmailLogController::class, 'index'])
        ->name('admin.notifications.logs.index');

    Route::post('marketing/communications/email-log/{id}/retry', [EmailLogController::class, 'retry'])
        ->whereNumber('id')
        ->middleware('throttle:30,1')
        ->name('admin.notifications.logs.retry');

    Route::controller(EmailTemplateController::class)->prefix('marketing/communications/customer-emails')->group(function () {
        Route::get('', 'index')->name('admin.notifications.templates.index');

        Route::get('{key}', 'edit')->name('admin.notifications.templates.edit');

        Route::put('{key}', 'update')->name('admin.notifications.templates.update');

        Route::get('{key}/preview/{format?}', 'preview')
            ->whereIn('format', ['html', 'text'])
            ->name('admin.notifications.templates.preview');

        /* Each test send is a real email: few per admin per hour (also limited in the controller). */
        Route::post('{key}/test', 'test')
            ->middleware('throttle:10,60')
            ->name('admin.notifications.templates.test');
    });

    Route::get('marketing/communications/customer-feedback', [FeedbackController::class, 'index'])
        ->name('admin.notifications.feedback.index');
});

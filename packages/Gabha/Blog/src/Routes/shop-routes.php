<?php

use Gabha\Blog\Http\Controllers\Shop\BlogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    /**
     * Public blog listing.
     */
    Route::get('blog', [BlogController::class, 'index'])
        ->name('shop.blog.index')
        ->middleware('cache.response');

    /**
     * Public single blog post. Declared after the listing so "blog" itself is
     * never captured as a slug. Laravel's fallback (CMS/product/category proxy)
     * always runs last, so these explicit routes take precedence.
     */
    Route::get('blog/{slug}', [BlogController::class, 'show'])
        ->name('shop.blog.show')
        ->middleware('cache.response');
});

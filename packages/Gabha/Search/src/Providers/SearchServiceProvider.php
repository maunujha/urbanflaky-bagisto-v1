<?php

declare(strict_types=1);

namespace Gabha\Search\Providers;

use Gabha\Search\Console\Commands\ReindexProducts;
use Gabha\Search\Listeners\ProductSearchSync;
use Gabha\Search\Repositories\ProductRepository as MeilisearchProductRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\Product\Repositories\ProductRepository as BaseProductRepository;

/**
 * Root service provider for the Gabha Search (Meilisearch) package.
 *
 * Wires the integration into Bagisto purely through Laravel's extension points
 * — a config merge, one container binding, three event listeners and the reindex
 * command. No Bagisto core file is required for the engine itself; the only core
 * touch-points (admin engine option, autocomplete branch) live outside this
 * provider and are independently reversible.
 */
class SearchServiceProvider extends ServiceProvider
{
    private string $packageRoot;

    public function register(): void
    {
        $this->packageRoot = dirname(__DIR__, 2);

        $this->mergeConfigFrom($this->packageRoot.'/src/Config/gabha-search.php', 'gabha-search');

        /*
         * Swap the Meilisearch-aware repository in for the core one. It only adds
         * a search branch to getAll() and otherwise defers to the parent, so any
         * code resolving ProductRepository keeps its existing behaviour.
         */
        $this->app->bind(BaseProductRepository::class, MeilisearchProductRepository::class);
    }

    public function boot(): void
    {
        $this->registerPublishables();
        $this->registerListeners();
        $this->registerCommands();
    }

    /**
     * Map Bagisto's product lifecycle events to the search sync handlers.
     */
    private function registerListeners(): void
    {
        Event::listen('catalog.product.create.after', [ProductSearchSync::class, 'afterCreate']);
        Event::listen('catalog.product.update.after', [ProductSearchSync::class, 'afterUpdate']);
        Event::listen('catalog.product.delete.before', [ProductSearchSync::class, 'beforeDelete']);
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ReindexProducts::class,
            ]);
        }
    }

    /**
     * Expose the package config to `php artisan vendor:publish`.
     */
    private function registerPublishables(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            $this->packageRoot.'/src/Config/gabha-search.php' => config_path('gabha-search.php'),
        ], 'gabha-search-config');
    }
}

<?php

declare(strict_types=1);

namespace Gabha\Search\Providers;

use Gabha\Search\Console\Commands\ReindexProducts;
use Gabha\Search\Console\Commands\SearchAnalyticsReport;
use Gabha\Search\Listeners\ProductSearchSync;
use Gabha\Search\Repositories\ProductRepository as MeilisearchProductRepository;
use Gabha\Search\Services\NaturalLanguage\CategoryResolver;
use Gabha\Search\Services\NaturalLanguage\QueryParser;
use Gabha\Search\Services\NaturalLanguage\SearchContext;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\Product\Repositories\ProductRepository as BaseProductRepository;

/**
 * Root service provider for the Gabha Search (Meilisearch) package.
 *
 * Wires the integration into Bagisto purely through Laravel's extension points
 * — config merges (engine settings, admin menu + ACL), container bindings, the
 * product-lifecycle listeners, migrations, admin routes/views and the console
 * commands. No Bagisto core file is required for the engine itself; the only core
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
         * Add the Search Insights item to the admin sidebar (under Catalog) and
         * its ACL entries, so the analytics page is reachable and permissionable.
         */
        $this->mergeConfigFrom($this->packageRoot.'/src/Config/menu.php', 'menu.admin');

        $this->mergeConfigFrom($this->packageRoot.'/src/Config/acl.php', 'acl');

        /*
         * Swap the Meilisearch-aware repository in for the core one. It only adds
         * a search branch to getAll() and otherwise defers to the parent, so any
         * code resolving ProductRepository keeps its existing behaviour.
         */
        $this->app->bind(BaseProductRepository::class, MeilisearchProductRepository::class);

        /*
         * The natural-language parser is pure and config-driven, so build it once
         * with the NL config block. CategoryResolver is a singleton so its
         * subtree lookups are memoised for the whole request.
         */
        $this->app->singleton(QueryParser::class, fn () => new QueryParser(
            (array) config('gabha-search.natural_language', [])
        ));

        $this->app->singleton(CategoryResolver::class);

        /*
         * Request-scoped carrier for shopper-facing search feedback (intent chips
         * + relaxation), populated by the repository and read back by the Shop
         * products API within the same request.
         */
        $this->app->singleton(SearchContext::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->packageRoot.'/src/Database/Migrations');

        $this->loadRoutesFrom($this->packageRoot.'/src/Routes/admin-routes.php');

        $this->loadViewsFrom($this->packageRoot.'/src/Resources/views', 'gabha-search');

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
                SearchAnalyticsReport::class,
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

        $this->publishes([
            $this->packageRoot.'/src/Database/Migrations' => database_path('migrations'),
        ], 'gabha-search-migrations');
    }
}

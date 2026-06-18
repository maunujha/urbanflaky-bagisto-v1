<?php

declare(strict_types=1);

namespace Gabha\Search\Listeners;

use Gabha\Search\Jobs\IndexProducts;
use Gabha\Search\Jobs\RemoveProducts;
use Webkul\Product\Repositories\ProductRepository;

/**
 * Keeps Meilisearch in sync with the catalog by listening to Bagisto's product
 * lifecycle events. These fire AFTER the product_flat index is refreshed, so the
 * document we build always reflects fully-persisted data — the same reason
 * Bagisto's native Elasticsearch indexer hooks these events rather than Eloquent
 * model events.
 *
 * Every handler is a no-op unless Meilisearch is the active engine, so the
 * package adds zero overhead (and is trivially reversible) when the store runs
 * on the database/elastic engine.
 */
class ProductSearchSync
{
    public function __construct(protected ProductRepository $productRepository) {}

    /**
     * @param  \Webkul\Product\Contracts\Product  $product
     */
    public function afterCreate($product): void
    {
        if (! $this->active()) {
            return;
        }

        IndexProducts::dispatch($this->relatedIds($product));
    }

    /**
     * @param  \Webkul\Product\Contracts\Product  $product
     */
    public function afterUpdate($product): void
    {
        if (! $this->active()) {
            return;
        }

        IndexProducts::dispatch($this->relatedIds($product));
    }

    /**
     * @param  int  $productId
     */
    public function beforeDelete($productId): void
    {
        if (! $this->active()) {
            return;
        }

        RemoveProducts::dispatch([(int) $productId]);

        /**
         * If a variant is removed, its configurable parent's document (price,
         * available colours) may change — reindex the parent.
         */
        $product = $this->productRepository->find($productId);

        if ($product && $product->parent_id) {
            IndexProducts::dispatch([(int) $product->parent_id]);
        }
    }

    /**
     * Whether Meilisearch is currently the active, enabled engine.
     */
    protected function active(): bool
    {
        return config('gabha-search.enabled', true)
            && core()->getConfigData('catalog.products.search.engine') === config('gabha-search.engine_value', 'meilisearch');
    }

    /**
     * The product plus any related products whose document is affected by the
     * change (a variant's configurable parent).
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return array<int, int>
     */
    protected function relatedIds($product): array
    {
        $ids = [(int) $product->id];

        if ($product->parent_id) {
            $ids[] = (int) $product->parent_id;
        }

        return array_values(array_unique($ids));
    }
}

<?php

declare(strict_types=1);

namespace Gabha\Search\Jobs;

use Gabha\Search\Models\Product;
use Gabha\Search\Services\SearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued (re)index of a set of products into Meilisearch.
 *
 * Loads each product, indexes the ones that should be searchable and removes
 * the rest (disabled, hidden, or deleted between dispatch and processing), so a
 * single job keeps the index consistent regardless of what changed. Runs on the
 * connection/queue from config so product saves never block on the search server.
 */
class IndexProducts implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Attempts before the job is marked failed (transient server errors retry).
     */
    public int $tries = 3;

    /**
     * @param  array<int, int>  $productIds
     */
    public function __construct(public array $productIds)
    {
        $this->onConnection(config('gabha-search.queue.connection'))
            ->onQueue(config('gabha-search.queue.queue'));
    }

    /**
     * Seconds to wait between retries.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(SearchService $search): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $this->productIds))));

        if (empty($ids)) {
            return;
        }

        $products = Product::query()
            ->with(['categories', 'attribute_values', 'price_indices'])
            ->whereIn('id', $ids)
            ->get();

        $documents = [];

        foreach ($products as $product) {
            if ($product->shouldBeSearchable()) {
                $documents[] = $product->toSearchableArray();
            }
        }

        $indexedIds = array_map(static fn ($document) => (int) $document['id'], $documents);

        $search->upsert($documents);

        /**
         * Anything we were asked to index but did not (missing, disabled, hidden
         * or a non-individually-visible variant) must not linger in the index.
         */
        $search->delete(array_values(array_diff($ids, $indexedIds)));
    }

    public function failed(Throwable $exception): void
    {
        report($exception);

        Log::channel((string) config('gabha-search.log_channel', 'meilisearch'))
            ->error('IndexProducts job failed.', [
                'product_ids' => $this->productIds,
                'error'       => $exception->getMessage(),
            ]);
    }
}

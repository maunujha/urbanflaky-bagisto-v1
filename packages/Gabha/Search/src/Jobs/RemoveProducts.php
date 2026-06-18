<?php

declare(strict_types=1);

namespace Gabha\Search\Jobs;

use Gabha\Search\Services\SearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued removal of product documents from Meilisearch (fired before a product
 * is deleted in the catalog).
 */
class RemoveProducts implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(SearchService $search): void
    {
        $search->delete($this->productIds);
    }

    public function failed(Throwable $exception): void
    {
        report($exception);

        Log::channel((string) config('gabha-search.log_channel', 'meilisearch'))
            ->error('RemoveProducts job failed.', [
                'product_ids' => $this->productIds,
                'error'       => $exception->getMessage(),
            ]);
    }
}

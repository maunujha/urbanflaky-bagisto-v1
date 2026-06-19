<?php

declare(strict_types=1);

namespace Gabha\Search\Jobs;

use Gabha\Search\Models\SearchQuery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Persists one search-analytics record off the request path.
 *
 * Queued so search latency is never affected. The driver decides the sink:
 *   - 'database' writes a row (degrading to a log warning if the table is absent);
 *   - 'log'      writes a single JSON line to the analytics channel;
 *   - 'null'     is a no-op (the service does not even dispatch in that case).
 *
 * Every path is wrapped so a logging failure can never bubble into search.
 */
class RecordSearchQuery implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    /**
     * @param  array<string, mixed>  $payload  columns for the search_queries row
     */
    public function __construct(public array $payload)
    {
        $this->onConnection(config('gabha-search.queue.connection'))
            ->onQueue(config('gabha-search.queue.queue'));
    }

    public function handle(): void
    {
        $driver = (string) config('gabha-search.analytics.driver', 'database');

        try {
            match ($driver) {
                'database' => $this->toDatabase(),
                'log'      => $this->toLog(),
                default    => null,
            };
        } catch (Throwable $e) {
            // Analytics must never break search — record the failure and move on.
            report($e);

            Log::channel((string) config('gabha-search.log_channel', 'meilisearch'))
                ->warning('Search analytics write failed; dropping the record.', [
                    'driver' => $driver,
                    'term'   => $this->payload['term'] ?? null,
                    'error'  => $e->getMessage(),
                ]);
        }
    }

    protected function toDatabase(): void
    {
        SearchQuery::create($this->payload);
    }

    protected function toLog(): void
    {
        Log::channel((string) config('gabha-search.analytics.channel', 'search-analytics'))
            ->info('search', $this->payload);
    }
}

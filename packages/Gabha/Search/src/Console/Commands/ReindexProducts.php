<?php

declare(strict_types=1);

namespace Gabha\Search\Console\Commands;

use Gabha\Search\Models\Product;
use Gabha\Search\Services\SearchService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Rebuilds the Meilisearch product index from scratch: (re)applies all index
 * settings (relevance, typo tolerance, synonyms) then bulk-imports every
 * individually-visible product in memory-safe batches.
 *
 *   php artisan urbanflaky:search:reindex            # upsert all products
 *   php artisan urbanflaky:search:reindex --fresh    # wipe the index first
 */
class ReindexProducts extends Command
{
    protected $signature = 'urbanflaky:search:reindex
                            {--fresh : Delete all existing documents before reindexing}';

    protected $description = 'Configure the Meilisearch index and (re)index all products';

    public function handle(SearchService $search): int
    {
        if (! config('gabha-search.enabled', true)) {
            $this->warn('Gabha Search is disabled (gabha-search.enabled = false). Nothing to do.');

            return self::SUCCESS;
        }

        $index = $search->indexName();

        $this->info("Configuring Meilisearch index \"{$index}\" (relevance, typo tolerance, synonyms) ...");

        try {
            $search->configureIndex();

            if ($this->option('fresh')) {
                $this->info('Flushing existing documents (--fresh) ...');

                $search->flush();
            }
        } catch (Throwable $e) {
            $search->logError('Reindex aborted: index configuration failed.', $e);

            $this->error('Failed to configure the index: '.$e->getMessage());

            return self::FAILURE;
        }

        $batchSize = max(1, (int) config('gabha-search.batch_size', 200));

        $total = Product::query()->whereNull('parent_id')->count();

        if ($total === 0) {
            $this->warn('No products found to index.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $indexed = 0;
        $failed = 0;

        Product::query()
            ->whereNull('parent_id')
            ->with(['categories', 'attribute_values', 'price_indices'])
            ->chunkById($batchSize, function ($products) use ($search, $bar, &$indexed, &$failed) {
                $documents = [];
                $removeIds = [];

                foreach ($products as $product) {
                    if ($product->shouldBeSearchable()) {
                        $documents[] = $product->toSearchableArray();
                    } else {
                        $removeIds[] = (int) $product->id;
                    }
                }

                try {
                    $search->upsert($documents);
                    $search->delete($removeIds);

                    $indexed += count($documents);
                } catch (Throwable $e) {
                    $failed += count($documents);

                    $search->logError('Reindex: a batch failed to index.', $e);
                }

                $bar->advance($products->count());
            });

        $bar->finish();

        $this->newLine(2);
        $this->info("Done. Indexed {$indexed} products into \"{$index}\".");

        if ($failed > 0) {
            $this->warn("{$failed} products failed — check the 'meilisearch' log channel.");
        }

        return self::SUCCESS;
    }
}

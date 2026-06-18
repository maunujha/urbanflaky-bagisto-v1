<?php

declare(strict_types=1);

namespace Gabha\Search\Services;

use Illuminate\Support\Facades\Log;
use Meilisearch\Client;
use Meilisearch\Endpoints\Indexes;
use Meilisearch\Exceptions\ApiException;
use Throwable;

/**
 * The single boundary between the application and the Meilisearch server.
 *
 * Owns index lifecycle (create + relevance/typo/synonym settings), document
 * upserts/deletes and the read path that returns ranked product ids. Every
 * server call is funnelled through here so error logging, the configured
 * connection and the index name have exactly one home.
 */
class SearchService
{
    protected ?Client $client = null;

    /**
     * Lazily-built Meilisearch client (host/key come from config/scout.php so
     * Scout's own engine and this service always talk to the same server).
     */
    public function client(): Client
    {
        if ($this->client instanceof Client) {
            return $this->client;
        }

        $config = (array) config('scout.meilisearch', []);

        return $this->client = new Client(
            $config['host'] ?? 'http://127.0.0.1:7700',
            $config['key'] ?? null
        );
    }

    /**
     * The product index endpoint.
     */
    public function index(): Indexes
    {
        return $this->client()->index($this->indexName());
    }

    public function indexName(): string
    {
        return (string) config('gabha-search.index', 'products');
    }

    /**
     * Create the index (primary key `id`) if absent, then push every relevance
     * knob: searchable/filterable/sortable attributes, ranking rules, typo
     * tolerance and the bidirectional synonyms. Idempotent — safe to re-run.
     */
    public function configureIndex(): void
    {
        $client = $this->client();
        $name = $this->indexName();

        if (! $this->indexExists($name)) {
            $this->waitOrFail($client->createIndex($name, ['primaryKey' => 'id']));
        }

        $this->waitOrFail($client->index($name)->updateSettings([
            'searchableAttributes' => array_values((array) config('gabha-search.searchable_attributes', [])),
            'filterableAttributes' => array_values((array) config('gabha-search.filterable_attributes', [])),
            'sortableAttributes'   => array_values((array) config('gabha-search.sortable_attributes', [])),
            'rankingRules'         => array_values((array) config('gabha-search.ranking_rules', [])),
            'typoTolerance'        => (array) config('gabha-search.typo_tolerance', []),
            'synonyms'             => $this->synonyms(),
        ]));
    }

    /**
     * Expand the configured synonym pairs into Meilisearch's bidirectional map.
     *
     * @return array<string, array<int, string>>
     */
    public function synonyms(): array
    {
        $map = [];

        foreach ((array) config('gabha-search.synonym_pairs', []) as $pair) {
            [$left, $right] = array_pad(array_values((array) $pair), 2, null);

            if (! $left || ! $right) {
                continue;
            }

            $map[$left][] = $right;
            $map[$right][] = $left;
        }

        return $map;
    }

    /**
     * Add or replace product documents (keyed on `id`).
     *
     * @param  array<int, array<string, mixed>>  $documents
     */
    public function upsert(array $documents): void
    {
        if (empty($documents)) {
            return;
        }

        $this->waitOrFail($this->index()->addDocuments(array_values($documents), 'id'));
    }

    /**
     * Remove product documents by id.
     *
     * @param  array<int, int>  $ids
     */
    public function delete(array $ids): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            return;
        }

        $this->waitOrFail($this->index()->deleteDocuments($ids));
    }

    /**
     * Empty the index without dropping its settings (used by reindex --fresh).
     */
    public function flush(): void
    {
        if (! $this->indexExists($this->indexName())) {
            return;
        }

        $this->waitOrFail($this->index()->deleteAllDocuments());
    }

    /**
     * Run a search and return product ids in relevance order plus an exact
     * total (page-based pagination yields a finite, paginatable total).
     *
     * @param  array{page?:int,limit?:int,sort?:array<int,string>,filter?:string}  $options
     * @return array{ids:array<int,int>,total:int}
     */
    public function search(string $query, array $options = []): array
    {
        $params = [
            'page'                 => max(1, (int) ($options['page'] ?? 1)),
            'hitsPerPage'          => max(1, (int) ($options['limit'] ?? 24)),
            'attributesToRetrieve' => ['id'],
        ];

        if (! empty($options['sort'])) {
            $params['sort'] = array_values((array) $options['sort']);
        }

        if (! empty($options['filter'])) {
            $params['filter'] = $options['filter'];
        }

        $result = $this->index()->search($query, $params);

        return [
            'ids'   => array_map(static fn ($hit) => (int) $hit['id'], $result->getHits()),
            'total' => (int) $result->getTotalHits(),
        ];
    }

    /**
     * Whether an index with the given uid already exists on the server.
     */
    protected function indexExists(string $name): bool
    {
        try {
            $this->client()->getRawIndex($name);

            return true;
        } catch (ApiException $e) {
            if ($e->httpStatus === 404) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * Block until an async Meilisearch task settles and fail loudly if it did
     * not succeed, so callers (jobs) can retry and the error is surfaced.
     *
     * @param  array<string, mixed>  $task
     */
    protected function waitOrFail(array $task): void
    {
        $uid = $task['taskUid'] ?? ($task['uid'] ?? null);

        if ($uid === null) {
            return;
        }

        $result = $this->client()->waitForTask($uid);

        if (($result['status'] ?? null) === 'failed') {
            $message = $result['error']['message'] ?? 'Unknown Meilisearch task failure';

            $this->logError('Meilisearch task failed: '.$message, $result);

            throw new \RuntimeException('Meilisearch task failed: '.$message);
        }
    }

    /**
     * Log to the dedicated Meilisearch channel and forward to Sentry.
     *
     * @param  array<string, mixed>|Throwable  $context
     */
    public function logError(string $message, array|Throwable $context = []): void
    {
        if ($context instanceof Throwable) {
            report($context);
            $context = ['exception' => $context->getMessage()];
        }

        Log::channel((string) config('gabha-search.log_channel', 'meilisearch'))
            ->error($message, $context);
    }
}

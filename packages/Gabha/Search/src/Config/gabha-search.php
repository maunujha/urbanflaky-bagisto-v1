<?php

/*
|--------------------------------------------------------------------------
| Gabha Search (Meilisearch) Configuration
|--------------------------------------------------------------------------
|
| Single source of truth for the Meilisearch product-search integration.
| Nothing in the package hard-codes index names, field lists, synonyms or
| relevance tuning — it is all read from here so the whole search behaviour
| can be tuned per-environment without touching code.
|
| The integration is a *third* Bagisto search engine. It only takes over the
| storefront search when the admin config `catalog.products.search.engine`
| equals `engine_value` below AND `enabled` is true; otherwise every hook is
| a no-op and the native database / elastic engines run untouched. That makes
| rollback a single admin toggle.
|
*/

return [
    /*
    |--------------------------------------------------------------------------
    | Master Toggle
    |--------------------------------------------------------------------------
    |
    | Hard kill-switch for the package. When false the service provider still
    | boots, but the repository override, the sync listeners and the autocomplete
    | branch all bail out — the store falls back to the native DB search.
    |
    */
    'enabled' => env('GABHA_SEARCH_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Engine Activation Value
    |--------------------------------------------------------------------------
    |
    | The value the admin `catalog.products.search.engine` select must hold for
    | Meilisearch to drive search. Matches the option added to the admin config.
    |
    */
    'engine_value' => 'meilisearch',

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    |
    | The Meilisearch index (uid) that holds product documents. Scout's own
    | `scout.prefix` is also honoured by the Searchable model via searchableAs().
    |
    */
    'index' => env('GABHA_SEARCH_INDEX', 'products'),

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Connection / queue used by the index + remove jobs. Defaults to the app's
    | queue connection. Indexing is always queued so product saves stay fast.
    |
    */
    'queue' => [
        'connection' => env('GABHA_SEARCH_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
        'queue'      => env('GABHA_SEARCH_QUEUE', 'default'),
    ],

    /*
    | How many products to push to Meilisearch per batch during a full reindex.
    */
    'batch_size' => (int) env('GABHA_SEARCH_BATCH_SIZE', 200),

    /*
    |--------------------------------------------------------------------------
    | Indexed Fields & Relevance
    |--------------------------------------------------------------------------
    |
    | `searchable_attributes` is ORDERED — earlier attributes carry more weight,
    | so a hit on `name` outranks a hit on `description`. Anything not listed is
    | stored but not searched. `ranking_rules` is Meilisearch's relevance
    | pipeline (left = highest priority).
    |
    */
    'searchable_attributes' => [
        'name',
        'sku',
        'category_names',
        'brand',
        'color',
        'tags',
        'short_description',
        'description',
    ],

    'filterable_attributes' => [
        'brand',
        'color',
        'price',
        'category_ids',
    ],

    'sortable_attributes' => [
        'name',
        'price',
        'created_at',
    ],

    'ranking_rules' => [
        'words',
        'typo',
        'proximity',
        'attribute',
        'sort',
        'exactness',
    ],

    /*
    |--------------------------------------------------------------------------
    | Typo Tolerance
    |--------------------------------------------------------------------------
    |
    | Words >= oneTypo length tolerate 1 typo; >= twoTypos tolerate 2. Short
    | tokens (sizes, SKUs) stay exact so "M" never matches "L".
    |
    */
    'typo_tolerance' => [
        'enabled'             => true,
        'minWordSizeForTypos' => [
            'oneTypo'  => 5,
            'twoTypos' => 9,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Synonyms (bidirectional)
    |--------------------------------------------------------------------------
    |
    | Declared as pairs; SearchService expands each pair both ways before
    | pushing to Meilisearch, so "tee" finds "tshirt" and vice-versa.
    |
    */
    'synonym_pairs' => [
        ['tshirt', 'tee'],
        ['black', 'dark'],
        ['oversized', 'baggy'],
        ['streetwear', 'urbanwear'],
        ['hoodie', 'sweatshirt'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tags Source
    |--------------------------------------------------------------------------
    |
    | This store has no native product-tags attribute, so `tags` indexes empty
    | by default. Point this at a source later without code changes:
    |   - null              => empty (current)
    |   - 'attribute:<code>'=> read a (future) product attribute, e.g. attribute:tags
    |   - 'category_brand'  => derive pseudo-tags from category names + brand + color
    |
    */
    'tags_source' => env('GABHA_SEARCH_TAGS_SOURCE', null),

    /*
    |--------------------------------------------------------------------------
    | Log Channel
    |--------------------------------------------------------------------------
    |
    | Dedicated channel (see config/logging.php) for all Meilisearch errors.
    |
    */
    'log_channel' => env('GABHA_SEARCH_LOG_CHANNEL', 'meilisearch'),
];

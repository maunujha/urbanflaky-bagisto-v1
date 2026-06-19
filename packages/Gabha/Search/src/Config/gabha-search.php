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

    /*
    |--------------------------------------------------------------------------
    | Natural-Language Search
    |--------------------------------------------------------------------------
    |
    | Turns a free-text query ("black oversized tee under 300", "oversized
    | tshirt for men") into structured Meilisearch filters + a cleaned full-text
    | query. The QueryParser is PURE (no DB / no framework) and reads everything
    | below, so the whole behaviour is tunable here without touching code.
    |
    | Mapping of responsibilities:
    |   - color      -> hard `color` filter (only the unambiguous words below).
    |   - price      -> hard `price` range filter (needs a cue: under/over/₹...).
    |   - gender     -> `category_ids` filter over the Mens/Womens category tree.
    |   - category   -> `category_ids` filter over a named section's tree.
    |   - product_type -> kept in the cleaned query (the index has no type facet),
    |                     so the Meilisearch synonym engine (tee<->tshirt,
    |                     hoodie<->sweatshirt) resolves it as full text.
    |
    */
    'natural_language' => [
        /*
        | Master toggle for the NL layer. When false the repository passes the
        | raw query straight to Meilisearch with only the explicit facet filters,
        | i.e. exactly the pre-NL behaviour.
        */
        'enabled' => env('GABHA_SEARCH_NL_ENABLED', true),

        /*
        | Query word => canonical colour as stored in the `color` attribute's
        | option admin_name. ONLY unambiguous colour words live here so they can
        | become hard filters. Vibe words ("dark", "aesthetic") are deliberately
        | absent — they stay in the full-text query, where the `black<->dark`
        | synonym still boosts relevance without hard-excluding anything.
        */
        'colors' => [
            'black'  => 'Black',
            'white'  => 'White',
            'red'    => 'Red',
            'green'  => 'Green',
            'yellow' => 'Yellow',
            'orange' => 'Orange',
            'blue'   => 'Blue',
            'navy'   => 'Blue',
            'pink'   => 'Pink',
            'purple' => 'Purple',
            'violet' => 'Purple',
            'grey'   => 'Grey',
            'gray'   => 'Grey',
            'brown'  => 'Brown',
        ],

        /*
        | Gender intent => the category slug whose whole subtree should be
        | filtered (resolved to ids + descendants at runtime via CategoryResolver).
        | `keywords` are matched as whole words in the query.
        */
        'gender' => [
            'men' => [
                'slug'     => 'mens',
                'keywords' => ['men', 'mens', 'man', 'male', 'males', 'gents', 'gent', 'boys', 'boy', 'guys'],
            ],
            'women' => [
                'slug'     => 'womens',
                'keywords' => ['women', 'womens', 'woman', 'female', 'females', 'ladies', 'lady', 'girls', 'girl'],
            ],
        ],

        /*
        | Product-type intent => its keyword aliases. Detected for analytics and
        | left in the cleaned query (no `product_type` facet is indexed), so the
        | Meilisearch synonyms below already cover tee/tshirt, hoodie/sweatshirt.
        */
        'product_types' => [
            'tshirt' => ['tshirt', 'tshirts', 't-shirt', 't-shirts', 'tee', 'tees'],
            'polo'   => ['polo', 'polos'],
            'hoodie' => ['hoodie', 'hoodies', 'sweatshirt', 'sweatshirts'],
            'shirt'  => ['shirt', 'shirts'],
            'combo'  => ['combo', 'combos', 'bundle', 'bundles'],
            'kurta'  => ['kurta', 'kurtas', 'anarkali'],
            'bottom' => ['bottom', 'bottoms', 'pant', 'pants', 'trouser', 'trousers', 'jeans', 'jean'],
        ],

        /*
        | Explicit section phrases => category slug. Lets a shopper name a section
        | directly ("bottom wear", "combos"); resolved to ids + descendants. Multi
        | word phrases are matched before single words.
        */
        'categories' => [
            'bottom wear' => 'bottom-wear',
            'bottomwear'  => 'bottom-wear',
            'bottom-wear' => 'bottom-wear',
            'combos'      => 'combos',
            'combo'       => 'combos',
        ],

        /*
        | Cue words that introduce a budget. Matched before a number so a bare
        | "100" (e.g. "100% cotton") is never mistaken for a price.
        */
        'price' => [
            'max_keywords'      => ['under', 'below', 'less than', 'lesser than', 'cheaper than', 'upto', 'up to', 'within', 'max', 'maximum', 'budget', 'budget of'],
            'min_keywords'      => ['over', 'above', 'more than', 'greater than', 'starting from', 'starting at', 'from', 'min', 'minimum', 'at least'],
            'currency_keywords' => ['₹', 'rs', 'rs.', 'inr', 'rupees', 'rupee'],
            /*
            | How to treat a number tagged ONLY with a currency cue and no
            | comparator ("under ₹300" is a max; "₹300" alone is...). 'max'
            | reads it as a budget ceiling; 'ignore' leaves it in the query.
            */
            'bare_currency_as' => 'max',
        ],

        /*
        | Stripped from the residual full-text query so connective words never
        | dilute relevance. Price/gender cue words are removed by their own
        | parsers; these are the extras.
        */
        'stopwords' => ['for', 'the', 'a', 'an', 'of', 'in', 'on', 'with', 'and', 'or', 'some', 'any', 'me', 'i', 'want', 'wanna', 'show', 'looking', 'need', 'find', 'please'],

        /*
        | Whether an inferred colour becomes a HARD `color` filter.
        |
        | Default false, on purpose: in this catalogue the `color` attribute is
        | largely unset (most products carry the colour only in their name), so a
        | hard filter would exclude almost everything and lean on relaxation. With
        | false the colour stays a full-text term — it still matches "Black" in a
        | product name and rides the black<->dark synonym, and is still recorded
        | as intent for analytics. Flip to true once products have their colour
        | attribute populated to get exact, precise colour filtering.
        */
        'color_as_filter' => env('GABHA_SEARCH_NL_COLOR_FILTER', false),

        /*
        | Progressive filter relaxation. If an NL-filtered search returns zero
        | hits, inferred filters are dropped one tier at a time (in `order`) and
        | the search retried, so a slightly-wrong guess never strands the shopper
        | on an empty page. ONLY NL-inferred filters relax — a shopper's explicit
        | facet selections (category page, price slider) are never touched.
        */
        'relaxation' => [
            'enabled' => env('GABHA_SEARCH_NL_RELAX', true),
            'order'   => ['color', 'price', 'category'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Analytics
    |--------------------------------------------------------------------------
    |
    | Captures every storefront search (term, parsed intent, result count,
    | whether relaxation rescued it) for merchandising insight — top searches,
    | zero-result terms, facet usage. Writes are queued (see `queue` above) so
    | search latency is unaffected, and always wrapped so an analytics failure
    | can never break search.
    |
    */
    'analytics' => [
        'enabled' => env('GABHA_SEARCH_ANALYTICS_ENABLED', true),

        /*
        | 'database' persists rows (queryable via urbanflaky:search:analytics);
        | 'log' writes JSON lines to the channel below only; 'null' disables it.
        | A 'database' write degrades to a log warning if the table is missing.
        */
        'driver' => env('GABHA_SEARCH_ANALYTICS_DRIVER', 'database'),

        'channel' => env('GABHA_SEARCH_ANALYTICS_CHANNEL', 'search-analytics'),

        /*
        | Don't bother recording blank/too-short queries (matches the storefront
        | min query length spirit) so the table stays signal-rich.
        */
        'min_length' => (int) env('GABHA_SEARCH_ANALYTICS_MIN_LENGTH', 2),
    ],
];

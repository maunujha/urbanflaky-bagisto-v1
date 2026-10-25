<?php

return [
    /*
     * Posts per page on the /blog listing. Kept high enough that every article
     * sits on page one, one click from the listing (crawl depth matters for
     * indexing on a small journal).
     */
    'per_page' => 12,

    /*
     * Number of related articles linked under each post.
     */
    'related_limit' => 3,

    /*
     * "Shop the edit" links under each post. A post matches a topic when any of
     * its keywords appears in the post's slug, title or meta keywords; the
     * collections of every matching topic are merged in order. Category slugs
     * that do not exist (or are disabled) are skipped at render time.
     */
    'collection_links' => [
        'limit' => 4,

        'topics' => [
            [
                'keywords'    => ['oversized', 'drop-shoulder', 'drop shoulder'],
                'collections' => ['oversized-t-shirts', 'heavyweight-oversized', 'drop-shoulder'],
            ],
            [
                'keywords'    => ['heavyweight', 'cotton', 'fabric', 'care'],
                'collections' => ['heavyweight-oversized', 'oversized-t-shirts'],
            ],
            [
                'keywords'    => ['dark', 'black', 'monochrome', 'colour', 'color'],
                'collections' => ['oversized-collection', 'graphic-oversized', 'oversized-t-shirts'],
            ],
            [
                'keywords'    => ['streetwear', 'minimal', 'capsule', 'wardrobe'],
                'collections' => ['oversized-collection', 'mens-t-shirts', 'womens', 'bottom-wear'],
            ],
        ],

        'default' => ['oversized-t-shirts', 'mens', 'womens', 'whats-new'],
    ],
];

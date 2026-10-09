<?php

/*
|--------------------------------------------------------------------------
| Legacy (Shopify) URL redirects
|--------------------------------------------------------------------------
|
| 301 targets for URLs from the old Shopify store that Google still crawls
| (Search Console "Not found (404)"). Consumed by RedirectLegacyUrls, which
| first normalises the request: lower-cases it, drops the query string
| (?variant=…&country=…) and strips the Shopify `/hi/` locale prefix.
|
| Keys are slugs within each Shopify URL type. Product slugs also match
| when requested bare (/{slug}) — nginx rewrites /products/{slug} to that.
| Old URLs with no sensible replacement are left out on purpose: a 404
| (dropped by Google over time) beats a misleading redirect.
|
*/

return [
    /* /products/{slug} and bare /{slug} */
    'products' => [
        'oversized-half-sleeves-round-neck-solid-pure-100-cotton-t-shirt-for-men' => '/mens-oversized-cotton-blend-drop-shoulder-t-shirt',
        'polo-tshirt-for-men-with-pocket-in-matty-fabric'                         => '/premium-matty-polo-t-shirt-men',
        'polo-tshirt-for-men-with-pocket-in-matty-fabric-pack-of-2'               => '/pack-of-2-mens-polo-tshirt-pocket-green-grey-cotton-matty-regular-fit',
        'polo-t-shirt-with-double-cool-techno-fit-fabric'                         => '/mens-slim-fit-double-cool-techno-fit-polo-tshirt',
        'mens-quick-dry-fit-regular-fit-t-shirt'                                  => '/mens-moisture-absorbing-round-neck-t-shirt',
        'women-crop-top-100-combed-cotton'                                        => '/womens-grey-crop-tshirt-made-on-demand',
        'techno-fit-fabric-pack-of-2-beige-pistagreen'                            => '/combos',
        'mens-quick-dry-fit-regular-fit-t-shirt-pack-of-3'                        => '/combos',
        'male-classic-crew-premium-t-shirt'                                       => '/mens-t-shirts',
        'urban-style-100-cotton-unisex-tee'                                       => '/mens-t-shirts',
        'unisex-acid-wash-oversized-tee-uc61'                                     => '/oversized-t-shirts',
        'unisex-terry-shorts-mt45-black'                                          => '/bottom-wear',
    ],

    /* /collections/{slug} */
    'collections' => [
        'all'                              => '/oversized-collection',
        'new-arrivals'                     => '/whats-new',
        'oversized-premium-fabric-t-shirts' => '/oversized-t-shirts',
        'pure-cotton-matty-polo-t-shirts'  => '/mens-t-shirts',
        'pod-t-shirts'                     => '/mens-t-shirts',
        'combo'                            => '/combos',
        'active-wear'                      => '/mens-t-shirts',
        'active-wear-female'               => '/womens',
    ],

    /* /pages/{slug} */
    'pages' => [
        'about-us' => '/about-us',
        'contact'  => '/contact-us',
        'sale'     => '/sale',
    ],

    /* /policies/{slug} */
    'policies' => [
        'contact-information' => '/contact-us',
        'privacy-policy'      => '/privacy-policy',
        'refund-policy'       => '/refund-policy',
        'shipping-policy'     => '/shipping-policy',
        'terms-of-service'    => '/terms-and-conditions',
    ],

    /* /blogs/{path} — a "{segment}/*" key matches every post under that blog */
    'blogs' => [
        'latest'             => '/blog',
        'latest/*'           => '/blog',
        'combo'              => '/combos',
        'active-wear'        => '/mens-t-shirts',
        'active-wear-female' => '/womens',
    ],

    /* Any other exact path */
    'paths' => [
        'blog/slim-fit-vs-regular-fit-which-t-shirt-is-right-for-you' => '/blog',
    ],
];

<?php

use Illuminate\Support\Facades\Storage;
use Webkul\Category\Models\Category;
use Webkul\Faker\Helpers\Category as CategoryFaker;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Product\Models\ProductPriceIndex;
use Webkul\Product\Models\ProductReview;
use Webkul\Shop\Helpers\StructuredData;

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Standard attribute set used by every product factory below.
 */
function structuredDataAttributes(): array
{
    return [
        'attributes' => [
            5  => 'new',
            6  => 'featured',
            11 => 'price',
            26 => 'guest_checkout',
        ],
        'attribute_value' => [
            'new'            => ['boolean_value' => true],
            'featured'       => ['boolean_value' => true],
            'price'          => ['float_value' => rand(400, 800)],
            'guest_checkout' => ['boolean_value' => true],
        ],
    ];
}

function makeSimpleProduct()
{
    return (new ProductFaker(structuredDataAttributes()))->getSimpleProductFactory()->create();
}

function makeConfigurableProduct()
{
    return (new ProductFaker(structuredDataAttributes()))->getConfigurableProductFactory()->create();
}

/**
 * Build the graph and assert it decodes to a valid JSON array.
 */
function buildGraph($product): array
{
    $json = app(StructuredData::class)->getProductGraph($product);

    $decoded = json_decode($json, true);

    expect(json_last_error())->toBe(JSON_ERROR_NONE);
    expect($decoded)->toBeArray();

    return $decoded;
}

function productNode(array $graph): ?array
{
    foreach ($graph['@graph'] as $node) {
        if (($node['@type'] ?? null) === 'Product') {
            return $node;
        }
    }

    return null;
}

function breadcrumbNode(array $graph): ?array
{
    foreach ($graph['@graph'] as $node) {
        if (($node['@type'] ?? null) === 'BreadcrumbList') {
            return $node;
        }
    }

    return null;
}

function countProductNodes(array $graph): int
{
    return collect($graph['@graph'])->where('@type', 'Product')->count();
}

/*
|--------------------------------------------------------------------------
| Tests
|--------------------------------------------------------------------------
*/

it('produces valid json with a single product node and schema context', function () {
    $graph = buildGraph(makeSimpleProduct());

    expect($graph['@context'])->toBe('https://schema.org');
    expect($graph['@graph'])->toBeArray();

    // No duplicate Product nodes.
    expect(countProductNodes($graph))->toBe(1);
});

it('builds a single Offer with a non-zero price for a simple product', function () {
    $product = makeSimpleProduct();

    $node = productNode(buildGraph($product));

    expect($node)->not->toBeNull();
    expect($node['name'])->toBe($product->name);
    expect($node['sku'])->toBe($product->sku);

    // Schema type selection: simple → Offer (never AggregateOffer).
    expect($node['offers']['@type'])->toBe('Offer');

    // No zero prices.
    expect((float) $node['offers']['price'])->toBeGreaterThan(0);
    expect($node['offers']['priceCurrency'])->toBe(core()->getCurrentCurrencyCode());
    expect($node['offers'])->toHaveKey('priceValidUntil');
});

it('builds a Product node for a configurable product', function () {
    $product = makeConfigurableProduct();

    $node = productNode(buildGraph($product));

    expect($node)->not->toBeNull();
    expect($node['@type'])->toBe('Product');
    expect($node['brand']['name'])->toBe('Urbanflaky');
});

it('emits an AggregateOffer with low/high price and offer count when variant prices differ', function () {
    $product = makeConfigurableProduct();

    /*
     * The data-faker gives every variant the same price, so force a genuine price
     * range on the parent's price index — exactly what a real multi-priced
     * configurable product produces — to exercise the AggregateOffer branch.
     */
    ProductPriceIndex::query()
        ->where('product_id', $product->id)
        ->update([
            'min_price'         => 399,
            'regular_min_price' => 399,
            'max_price'         => 799,
            'regular_max_price' => 799,
        ]);

    $product = app(\Webkul\Product\Repositories\ProductRepository::class)->find($product->id);

    $node = productNode(buildGraph($product));

    expect($node['offers']['@type'])->toBe('AggregateOffer');
    expect((float) $node['offers']['lowPrice'])->toBe(399.00);
    expect((float) $node['offers']['highPrice'])->toBe(799.00);
    expect((float) $node['offers']['lowPrice'])->toBeGreaterThan(0);
    expect($node['offers']['offerCount'])->toBe($product->variants->count());
    expect($node['offers'])->not->toHaveKey('priceValidUntil');
});

it('never outputs a zero or negative price', function () {
    $product = makeConfigurableProduct();

    $node = productNode(buildGraph($product));

    $offer = $node['offers'];

    $price = (float) ($offer['price'] ?? $offer['lowPrice'] ?? 0);

    expect($price)->toBeGreaterThan(0);
});

it('outputs all gallery images as an array', function () {
    Storage::fake();

    $product = makeSimpleProduct();

    $paths = [
        "product/{$product->id}/image-1.webp",
        "product/{$product->id}/image-2.webp",
        "product/{$product->id}/image-3.webp",
    ];

    foreach ($paths as $position => $path) {
        Storage::put($path, 'binary-content');

        $product->images()->create([
            'type'     => 'image',
            'path'     => $path,
            'position' => $position + 1,
        ]);
    }

    $product->load('images');

    $node = productNode(buildGraph($product));

    expect($node['image'])->toBeArray();
    expect($node['image'])->toHaveCount(3);

    foreach ($node['image'] as $url) {
        expect($url)->toBeString()->not->toBeEmpty();
    }
});

it('still produces valid output for a product without gallery images', function () {
    $product = makeSimpleProduct();

    expect($product->images)->toHaveCount(0);

    $graph = buildGraph($product);
    $node  = productNode($graph);

    expect($node)->not->toBeNull();
    expect(countProductNodes($graph))->toBe(1);

    // When an image key is present it must be a non-empty array (base-image fallback),
    // never an empty string or empty array.
    if (array_key_exists('image', $node)) {
        expect($node['image'])->toBeArray()->not->toBeEmpty();
    }
});

it('includes an AggregateRating for products with approved reviews', function () {
    $product = makeSimpleProduct();

    ProductReview::factory()->count(3)->create([
        'product_id' => $product->id,
        'status'     => 'approved',
        'rating'     => 5,
    ]);

    $product->load('reviews');

    $node = productNode(buildGraph($product));

    expect($node)->toHaveKey('aggregateRating');
    expect($node['aggregateRating']['@type'])->toBe('AggregateRating');
    expect((int) $node['aggregateRating']['reviewCount'])->toBe(3);
    expect((float) $node['aggregateRating']['ratingValue'])->toBeGreaterThan(0);
    expect($node['aggregateRating']['bestRating'])->toBe('5');
});

it('emits individual Review objects for approved reviews only', function () {
    $product = makeSimpleProduct();

    ProductReview::factory()->count(2)->create([
        'product_id' => $product->id,
        'status'     => 'approved',
        'rating'     => 4,
        'title'      => 'Great fit',
        'comment'    => 'Loved the fabric and the fit.',
    ]);

    // Unapproved reviews must be excluded from the review array.
    ProductReview::factory()->count(3)->create([
        'product_id' => $product->id,
        'status'     => 'pending',
        'rating'     => 1,
    ]);

    $product->load('reviews');

    $node = productNode(buildGraph($product));

    expect($node)->toHaveKey('review');
    expect($node['review'])->toBeArray()->toHaveCount(2);

    $review = $node['review'][0];

    expect($review['@type'])->toBe('Review');
    expect($review['reviewRating']['@type'])->toBe('Rating');
    expect($review['reviewRating']['ratingValue'])->toBe('4');
    expect($review['reviewRating']['bestRating'])->toBe('5');
    expect($review['author']['@type'])->toBe('Person');
    expect($review['author']['name'])->not->toBeEmpty();
    expect($review)->toHaveKey('datePublished');
    expect($review['name'])->toBe('Great fit');
    expect($review['reviewBody'])->toBe('Loved the fabric and the fit.');
});

it('omits the review array when the product has no approved reviews', function () {
    $product = makeSimpleProduct();

    ProductReview::factory()->count(2)->create([
        'product_id' => $product->id,
        'status'     => 'pending',
        'rating'     => 5,
    ]);

    $product->load('reviews');

    $node = productNode(buildGraph($product));

    expect($node)->not->toHaveKey('review');
});

it('omits AggregateRating when the product has no approved reviews', function () {
    $product = makeSimpleProduct();

    // Pending reviews must not count toward the rating snippet.
    ProductReview::factory()->count(2)->create([
        'product_id' => $product->id,
        'status'     => 'pending',
        'rating'     => 4,
    ]);

    $product->load('reviews');

    $node = productNode(buildGraph($product));

    expect($node)->not->toHaveKey('aggregateRating');
});

it('generates a BreadcrumbList with the Home > Category > Product hierarchy', function () {
    $product = makeSimpleProduct();

    $category = (new CategoryFaker)->factory()->create();

    $product->categories()->sync([$category->id]);
    $product->load('categories');

    $breadcrumb = breadcrumbNode(buildGraph($product));

    expect($breadcrumb)->not->toBeNull();
    expect($breadcrumb['@type'])->toBe('BreadcrumbList');

    $items = $breadcrumb['itemListElement'];

    expect($items)->toHaveCount(3);

    // Sequential positions starting at 1.
    expect($items[0]['position'])->toBe(1);
    expect($items[1]['position'])->toBe(2);
    expect($items[2]['position'])->toBe(3);

    // Hierarchy: Home > Category > Product.
    expect($items[0]['name'])->toBe('Home');
    expect($items[1]['name'])->toBe($category->name);
    expect($items[2]['name'])->toBe($product->name);
});

it('falls back to Home > Product when the product has no category', function () {
    $product = makeSimpleProduct();

    expect($product->categories)->toHaveCount(0);

    $breadcrumb = breadcrumbNode(buildGraph($product));

    $items = $breadcrumb['itemListElement'];

    expect($items)->toHaveCount(2);
    expect($items[0]['name'])->toBe('Home');
    expect($items[1]['name'])->toBe($product->name);
});

it('selects Offer for simple and AggregateOffer for ranged configurable products', function () {
    // Simple → Offer.
    $simpleNode = productNode(buildGraph(makeSimpleProduct()));

    expect($simpleNode['offers']['@type'])->toBe('Offer');

    // Configurable with a forced price range → AggregateOffer.
    $configurable = makeConfigurableProduct();

    ProductPriceIndex::query()
        ->where('product_id', $configurable->id)
        ->update([
            'min_price'         => 299,
            'regular_min_price' => 299,
            'max_price'         => 599,
            'regular_max_price' => 599,
        ]);

    $configurable = app(\Webkul\Product\Repositories\ProductRepository::class)->find($configurable->id);

    $configurableNode = productNode(buildGraph($configurable));

    expect($configurableNode['offers']['@type'])->toBe('AggregateOffer');
});

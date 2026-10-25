<?php

namespace Webkul\Shop\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Product\Helpers\Review as ReviewHelper;

class StructuredData
{
    /**
     * Brand shown in the Product schema.
     */
    const BRAND_NAME = 'Urbanflaky';

    /**
     * Selling merchant / organisation.
     */
    const SELLER_NAME = 'Gabha Enterprise';

    /**
     * Build the full JSON-LD graph (Product + BreadcrumbList) for a product detail page.
     *
     * This is the single source of truth for PDP structured data. The result is ready to be
     * printed verbatim inside a <script type="application/ld+json"> tag.
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return string
     */
    public function getProductGraph($product): string
    {
        $graph = [
            $this->getProductNode($product),
        ];

        if ($breadcrumb = $this->getBreadcrumbNode($product)) {
            $graph[] = $breadcrumb;
        }

        return json_encode([
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Build the full JSON-LD graph (CollectionPage + BreadcrumbList) for a category listing page.
     *
     * Replaces the core rich-snippets category JSON-LD, which only emitted a generic WebSite
     * node carrying no category data at all.
     *
     * @param  \Webkul\Category\Contracts\Category  $category
     * @return string
     */
    public function getCategoryGraph($category): string
    {
        $url = $category->url;

        $collectionPage = [
            '@type' => 'CollectionPage',
            '@id'   => $url.'#webpage',
            'url'   => $url,
            'name'  => trim((string) $category->meta_title) !== '' ? $category->meta_title : $category->name,
        ];

        if ($description = trim(strip_tags((string) $category->description))) {
            $collectionPage['description'] = Str::limit($description, 5000, '');
        }

        $items = [[
            '@type'    => 'ListItem',
            'position' => 1,
            'name'     => 'Home',
            'item'     => route('shop.home.index'),
        ]];

        $position = 2;

        /* Parent trail — the root category is skipped because it has no public page. */
        foreach ($category->ancestors as $ancestor) {
            if (! $ancestor->parent_id) {
                continue;
            }

            $items[] = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => $ancestor->name,
                'item'     => $ancestor->url,
            ];
        }

        $items[] = [
            '@type'    => 'ListItem',
            'position' => $position,
            'name'     => $category->name,
            'item'     => $url,
        ];

        return json_encode([
            '@context' => 'https://schema.org',
            '@graph'   => [
                $collectionPage,
                [
                    '@type'           => 'BreadcrumbList',
                    'itemListElement' => $items,
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Assemble the Product node.
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return array
     */
    protected function getProductNode($product): array
    {
        $url = route('shop.product_or_category.index', $product->url_key);

        $node = [
            '@type'       => 'Product',
            '@id'         => $url.'#product',
            'name'        => $product->name,
            'description' => $this->getDescription($product),
            'sku'         => $product->sku,
            'url'         => $url,
            'brand'       => [
                '@type' => 'Brand',
                'name'  => self::BRAND_NAME,
            ],
        ];

        if ($images = $this->getImages($product)) {
            $node['image'] = $images;
        }

        if ($offers = $this->getOffers($product, $url)) {
            $node['offers'] = $offers;
        }

        if ($rating = $this->getAggregateRating($product)) {
            $node['aggregateRating'] = $rating;
        }

        if ($reviews = $this->getReviews($product)) {
            $node['review'] = $reviews;
        }

        return $node;
    }

    /**
     * Build the Offer / AggregateOffer node.
     *
     * Uses Bagisto's price-index aware minimal/maximum prices so configurable, simple, grouped,
     * virtual and downloadable products all resolve a correct, non-zero price. When the price
     * spans a range (e.g. configurable variants) an AggregateOffer with lowPrice / highPrice /
     * offerCount is emitted; otherwise a single Offer.
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @param  string  $url
     * @return array|null
     */
    protected function getOffers($product, string $url): ?array
    {
        $typeInstance = $product->getTypeInstance();

        $min = (float) $typeInstance->getMinimalPrice();
        $max = (float) $typeInstance->getMaximumPrice();

        /* Fall back to the product's own price when the index is unavailable. */
        if ($min <= 0) {
            $min = (float) $product->price;
        }

        if ($max < $min) {
            $max = $min;
        }

        /* Never emit a zero / negative price — omit the offer instead of breaking the schema. */
        if ($min <= 0) {
            return null;
        }

        $common = [
            'priceCurrency' => core()->getCurrentCurrencyCode(),
            'availability'  => $product->isSaleable(1)
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
            'url'           => $url,
            'seller'        => [
                '@type' => 'Organization',
                'name'  => self::SELLER_NAME,
            ],
            'shippingDetails' => $this->getShippingDetails($product),
        ];

        if ($validFrom = $product->created_at?->format('Y-m-d')) {
            $common['validFrom'] = $validFrom;
        }

        if ($returnPolicy = $this->getReturnPolicy($product)) {
            $common['hasMerchantReturnPolicy'] = $returnPolicy;
        }

        /* Price varies across variants → AggregateOffer. */
        if ($max > $min) {
            return array_merge([
                '@type'      => 'AggregateOffer',
                'lowPrice'   => $this->formatPrice($min),
                'highPrice'  => $this->formatPrice($max),
                'offerCount' => $this->getOfferCount($product),
            ], $common);
        }

        /* Single, fixed price → Offer (priceValidUntil only applies here). */
        return array_merge([
            '@type'           => 'Offer',
            'price'           => $this->formatPrice($min),
            'priceValidUntil' => now()->addYear()->format('Y-m-d'),
        ], $common);
    }

    /**
     * OfferShippingDetails from the flat-rate carrier config (sales.carriers.flatrate).
     *
     * deliveryTime is emitted only when every handling/transit day bound is configured
     * (config/products.php → structured_data.delivery_time), so the schema never states
     * a delivery promise the store has not made. Made-on-Demand products are produced
     * after ordering, so the ready-stock dispatch window does not apply to them.
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return array
     */
    protected function getShippingDetails($product): array
    {
        $details = [
            '@type'        => 'OfferShippingDetails',
            'shippingRate' => [
                '@type'    => 'MonetaryAmount',
                'value'    => (string) (core()->getConfigData('sales.carriers.flatrate.default_rate') ?: 50),
                'currency' => 'INR',
            ],
            'shippingDestination' => [
                '@type'          => 'DefinedRegion',
                'addressCountry' => 'IN',
            ],
        ];

        $days = config('products.structured_data.delivery_time', []);

        $bounds = [
            'handling_min' => $days['handling_min'] ?? null,
            'handling_max' => $days['handling_max'] ?? null,
            'transit_min'  => $days['transit_min'] ?? null,
            'transit_max'  => $days['transit_max'] ?? null,
        ];

        if ($product->is_made_on_demand || in_array(null, $bounds, true)) {
            return $details;
        }

        $details['deliveryTime'] = [
            '@type'        => 'ShippingDeliveryTime',
            'handlingTime' => [
                '@type'    => 'QuantitativeValue',
                'minValue' => (int) $bounds['handling_min'],
                'maxValue' => (int) $bounds['handling_max'],
                'unitCode' => 'DAY',
            ],
            'transitTime' => [
                '@type'    => 'QuantitativeValue',
                'minValue' => (int) $bounds['transit_min'],
                'maxValue' => (int) $bounds['transit_max'],
                'unitCode' => 'DAY',
            ],
        ];

        return $details;
    }

    /**
     * MerchantReturnPolicy built from the return window the RMA module actually enforces
     * for this product — the same eligibility rules OrderItemResource snapshots at checkout:
     * allowed product type, allow_rma enabled, then the product's active RMA rule period or
     * the global sales.rma.setting.default_allow_days.
     *
     * Omitted for products the store would not accept back, so the schema never promises
     * a return the RMA flow refuses.
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return array|null
     */
    protected function getReturnPolicy($product): ?array
    {
        $allowedTypes = array_map('trim', explode(',', (string) core()->getConfigData('sales.rma.setting.select_allowed_product_type')));

        if (! in_array($product->type, $allowedTypes, true)) {
            return null;
        }

        $attributeIds = DB::table('attributes')->whereIn('code', ['allow_rma', 'rma_rule_id'])->pluck('id', 'code');

        /* Stored values only, exactly as the RMA flow reads them — the attribute default is ignored there. */
        $allowRma = DB::table('product_attribute_values')
            ->where('product_id', $product->id)
            ->where('attribute_id', $attributeIds['allow_rma'] ?? 0)
            ->value('boolean_value');

        if ((int) $allowRma !== 1) {
            return null;
        }

        $rulePeriod = DB::table('product_attribute_values')
            ->where('product_id', $product->id)
            ->where('attribute_id', $attributeIds['rma_rule_id'] ?? 0)
            ->join('rma_rules', function ($join) {
                $join->on('product_attribute_values.integer_value', '=', 'rma_rules.id')
                    ->where('rma_rules.status', 1);
            })
            ->value('rma_rules.return_period');

        $days = (int) ($rulePeriod ?? core()->getConfigData('sales.rma.setting.default_allow_days'));

        if ($days < 1) {
            return null;
        }

        return [
            '@type'                => 'MerchantReturnPolicy',
            'applicableCountry'    => 'IN',
            'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
            'merchantReturnDays'   => $days,
            'returnMethod'         => 'https://schema.org/ReturnByMail',
            'merchantReturnLink'   => url('return-policy'),
        ];
    }

    /**
     * Number of individual offers behind an AggregateOffer.
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return int
     */
    protected function getOfferCount($product): int
    {
        if ($product->type === 'configurable') {
            return max(1, $product->variants->count());
        }

        if ($product->type === 'grouped') {
            return max(1, $product->grouped_products->count());
        }

        return 1;
    }

    /**
     * All gallery images as an array of absolute URLs.
     *
     * Falls back to the computed base image when the product has no stored gallery images.
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return array
     */
    protected function getImages($product): array
    {
        $urls = [];

        foreach ($product->images as $image) {
            if (empty($image->path) || ! Storage::has($image->path)) {
                continue;
            }

            if ($image->url) {
                $urls[] = $image->url;
            }
        }

        if (empty($urls)) {
            $baseImage = product_image()->getProductBaseImage($product);

            if (! empty($baseImage['medium_image_url'])) {
                $urls[] = $baseImage['medium_image_url'];
            }
        }

        return $urls;
    }

    /**
     * Resolve the product description with graceful fallbacks:
     * description → meta_description → short_description → name.
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return string
     */
    protected function getDescription($product): string
    {
        $candidates = [
            $product->description,
            $product->meta_description,
            $product->short_description,
        ];

        foreach ($candidates as $candidate) {
            $text = trim(strip_tags((string) $candidate));

            if ($text !== '') {
                return Str::limit($text, 5000, '');
            }
        }

        return $product->name;
    }

    /**
     * Aggregate rating node, only when the product has approved feedback.
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return array|null
     */
    protected function getAggregateRating($product): ?array
    {
        $reviewHelper = app(ReviewHelper::class);

        /* reviewCount must be the number of reviews, not the rating sum that
           getTotalFeedback() may return under the "star_counts" summary config. */
        $count = (int) $reviewHelper->getTotalReviews($product);

        if ($count < 1) {
            return null;
        }

        return [
            '@type'       => 'AggregateRating',
            'ratingValue' => $reviewHelper->getAverageRating($product),
            'reviewCount' => $count,
            'bestRating'  => '5',
            'worstRating' => '1',
        ];
    }

    /**
     * Individual Review nodes for approved reviews only.
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return array
     */
    protected function getReviews($product): array
    {
        $reviews = [];

        $approved = $product->reviews
            ->where('status', 'approved')
            ->sortByDesc('created_at');

        foreach ($approved as $review) {
            $node = [
                '@type'        => 'Review',
                'reviewRating' => [
                    '@type'       => 'Rating',
                    'ratingValue' => (string) $review->rating,
                    'bestRating'  => '5',
                    'worstRating' => '1',
                ],
                'author' => [
                    '@type' => 'Person',
                    'name'  => $review->name ?: 'Anonymous',
                ],
            ];

            if ($review->created_at) {
                $node['datePublished'] = $review->created_at->format('Y-m-d');
            }

            if ($title = trim((string) $review->title)) {
                $node['name'] = $title;
            }

            if ($body = trim(strip_tags((string) $review->comment))) {
                $node['reviewBody'] = Str::limit($body, 5000, '');
            }

            $reviews[] = $node;
        }

        return $reviews;
    }

    /**
     * BreadcrumbList node — Home > Category > Product.
     *
     * The category segment uses the product's first associated category (when present) so the
     * hierarchy is richer than the route-level "Home > Product" trail.
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return array|null
     */
    protected function getBreadcrumbNode($product): ?array
    {
        $items = [];

        $position = 1;

        /* Home */
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => 'Home',
            'item'     => route('shop.home.index'),
        ];

        /* Category (first associated, when available) */
        if ($category = $product->categories->first()) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => $category->name,
                'item'     => $category->url,
            ];
        }

        /* Product (current page — no item URL, per Google guidance for the last crumb) */
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => $product->name,
            'item'     => route('shop.product_or_category.index', $product->url_key),
        ];

        return [
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Format a price for schema output: plain decimal, no thousands separator.
     *
     * @param  float  $price
     * @return string
     */
    protected function formatPrice(float $price): string
    {
        return number_format($price, 2, '.', '');
    }
}

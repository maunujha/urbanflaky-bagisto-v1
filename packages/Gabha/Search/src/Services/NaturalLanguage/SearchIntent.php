<?php

declare(strict_types=1);

namespace Gabha\Search\Services\NaturalLanguage;

/**
 * The structured result of parsing a free-text storefront query.
 *
 * A plain, framework-free value object so it is trivial to build in tests and
 * to serialise for the analytics job. Every facet is nullable — absence simply
 * means the parser found no signal for it, and the consumer (the repository)
 * decides which facets become hard Meilisearch filters versus full-text.
 *
 * `cleanQuery` is what should actually be sent to Meilisearch's full-text
 * search: the original query minus the spans consumed by price/colour/gender
 * parsing and minus stopwords. It can legitimately be an empty string (e.g.
 * "black under 300" reduces to "" with a colour + price filter), in which case
 * the filters alone drive the result set.
 */
final class SearchIntent
{
    /**
     * @param  string                              $original     the raw, untouched query
     * @param  string                              $cleanQuery   residual full-text terms
     * @param  string|null                         $color        canonical colour (option admin_name)
     * @param  float|null                          $priceMin     inclusive lower bound in base currency
     * @param  float|null                          $priceMax     inclusive upper bound in base currency
     * @param  string|null                         $gender       'men' | 'women'
     * @param  string|null                         $productType  canonical product-type token
     * @param  string|null                         $categorySlug explicitly named section slug
     * @param  array<string, string>               $matches      facet => matched source text (analytics)
     */
    public function __construct(
        public readonly string $original,
        public readonly string $cleanQuery,
        public readonly ?string $color = null,
        public readonly ?float $priceMin = null,
        public readonly ?float $priceMax = null,
        public readonly ?string $gender = null,
        public readonly ?string $productType = null,
        public readonly ?string $categorySlug = null,
        public readonly array $matches = [],
    ) {}

    /**
     * Whether the parser extracted any facet at all. False means the query is a
     * plain keyword/SKU search and behaves exactly as it did before the NL layer.
     */
    public function hasIntent(): bool
    {
        return $this->color !== null
            || $this->priceMin !== null
            || $this->priceMax !== null
            || $this->gender !== null
            || $this->productType !== null
            || $this->categorySlug !== null;
    }

    /**
     * Whether any facet that the repository turns into a hard filter is present.
     * (product_type stays in the full-text query, so it does not count here.)
     */
    public function hasFilterableIntent(): bool
    {
        return $this->color !== null
            || $this->priceMin !== null
            || $this->priceMax !== null
            || $this->gender !== null
            || $this->categorySlug !== null;
    }

    /**
     * A flat, serialisable snapshot for logging / the analytics job.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'original'      => $this->original,
            'clean_query'   => $this->cleanQuery,
            'color'         => $this->color,
            'price_min'     => $this->priceMin,
            'price_max'     => $this->priceMax,
            'gender'        => $this->gender,
            'product_type'  => $this->productType,
            'category_slug' => $this->categorySlug,
            'matches'       => $this->matches,
        ];
    }
}

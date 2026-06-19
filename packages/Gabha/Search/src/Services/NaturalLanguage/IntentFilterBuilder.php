<?php

declare(strict_types=1);

namespace Gabha\Search\Services\NaturalLanguage;

/**
 * Composes a {@see FilterSet} from the request's explicit facet params and the
 * parsed {@see SearchIntent}.
 *
 * Precedence rule: an explicit shopper choice always wins over an inferred one.
 * If the request already carries a `category_id` (a category page) the gender /
 * section intent is ignored; if it carries a `price` (the slider) the parsed
 * budget is ignored. The remaining intent becomes relaxable NL clauses.
 *
 * Meilisearch filter syntax produced here matches the index's `filterable_attributes`
 * (color, price, category_ids) configured in config/gabha-search.php.
 */
class IntentFilterBuilder
{
    public function __construct(protected CategoryResolver $categories) {}

    /**
     * @param  array<string, mixed>  $params  the repository search params
     */
    public function build(array $params, SearchIntent $intent): FilterSet
    {
        return new FilterSet($this->fixedClauses($params), $this->nlClauses($params, $intent));
    }

    /**
     * Clauses from explicit facet selections — authoritative, never relaxed.
     *
     * @param  array<string, mixed>  $params
     * @return array<int, string>
     */
    protected function fixedClauses(array $params): array
    {
        $clauses = [];

        if (! empty($params['category_id'])) {
            $ids = $this->intList((string) $params['category_id']);

            if ($ids) {
                $clauses[] = 'category_ids IN ['.implode(', ', $ids).']';
            }
        }

        if (! empty($params['price'])) {
            $range = explode(',', (string) $params['price']);

            $min = core()->convertToBasePrice((float) current($range));
            $max = core()->convertToBasePrice((float) end($range));

            $clauses[] = 'price >= '.$min.' AND price <= '.$max;
        }

        return $clauses;
    }

    /**
     * Inferred clauses, keyed by relaxation tier. Each is suppressed when the
     * shopper already pinned that dimension explicitly.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, array<int, string>>
     */
    protected function nlClauses(array $params, SearchIntent $intent): array
    {
        $nl = [
            'color'    => [],
            'price'    => [],
            'category' => [],
        ];

        if (
            $intent->color !== null
            && config('gabha-search.natural_language.color_as_filter', true)
        ) {
            $nl['color'][] = 'color = "'.$this->escape($intent->color).'"';
        }

        if (empty($params['price']) && ($intent->priceMin !== null || $intent->priceMax !== null)) {
            $nl['price'][] = $this->priceClause($intent);
        }

        // Gender + named section are independent constraints (a product must be in
        // both subtrees), so they are emitted as separate AND clauses under one tier.
        if (empty($params['category_id'])) {
            if ($intent->gender !== null) {
                $ids = $this->categories->genderIds($intent->gender);

                if ($ids) {
                    $nl['category'][] = 'category_ids IN ['.implode(', ', $ids).']';
                }
            }

            if ($intent->categorySlug !== null) {
                $ids = $this->categories->descendantIds($intent->categorySlug);

                if ($ids) {
                    $nl['category'][] = 'category_ids IN ['.implode(', ', $ids).']';
                }
            }
        }

        return $nl;
    }

    /**
     * Build the price range clause from whichever bounds the parser found.
     */
    protected function priceClause(SearchIntent $intent): string
    {
        $parts = [];

        if ($intent->priceMin !== null) {
            $parts[] = 'price >= '.core()->convertToBasePrice($intent->priceMin);
        }

        if ($intent->priceMax !== null) {
            $parts[] = 'price <= '.core()->convertToBasePrice($intent->priceMax);
        }

        return implode(' AND ', $parts);
    }

    /**
     * @return array<int, int>
     */
    protected function intList(string $csv): array
    {
        return array_values(array_filter(array_map('intval', explode(',', $csv))));
    }

    /**
     * Escape a value for a double-quoted Meilisearch filter literal.
     */
    protected function escape(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }
}

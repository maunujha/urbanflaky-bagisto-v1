<?php

declare(strict_types=1);

namespace Gabha\Search\Services\NaturalLanguage;

/**
 * A composed set of Meilisearch filter clauses split into two buckets:
 *
 *   - `fixed`: clauses from the shopper's explicit choices (a category page, the
 *     price slider). Always applied; never relaxed.
 *   - `nl`:    clauses inferred from the typed query, keyed by relaxation tier
 *     ('color' | 'price' | 'category'). These may be dropped one tier at a time
 *     when a search returns nothing, so a wrong guess never yields an empty page.
 *
 * Each clause is wrapped in parentheses when joined so an internal `AND`
 * (e.g. a price range) can never bleed into the surrounding expression.
 */
final class FilterSet
{
    /**
     * @param  array<int, string>          $fixed
     * @param  array<string, array<int, string>>  $nl  tier => clauses
     */
    public function __construct(
        protected array $fixed = [],
        protected array $nl = [],
    ) {}

    /**
     * The Meilisearch filter expression, optionally with some NL tiers dropped.
     *
     * @param  array<int, string>  $dropTiers  NL tiers to omit (relaxation)
     */
    public function expression(array $dropTiers = []): ?string
    {
        $clauses = $this->fixed;

        foreach ($this->nl as $tier => $tierClauses) {
            if (in_array($tier, $dropTiers, true)) {
                continue;
            }

            $clauses = array_merge($clauses, $tierClauses);
        }

        $clauses = array_values(array_filter($clauses));

        if (empty($clauses)) {
            return null;
        }

        return implode(' AND ', array_map(static fn ($clause) => '('.$clause.')', $clauses));
    }

    /**
     * NL tiers that actually produced a clause, in the configured drop order.
     * Drives both relaxation iteration and analytics ("which filters applied").
     *
     * @param  array<int, string>  $order
     * @return array<int, string>
     */
    public function relaxableTiers(array $order): array
    {
        return array_values(array_filter(
            $order,
            fn ($tier) => ! empty($this->nl[$tier] ?? [])
        ));
    }

    public function hasNlFilters(): bool
    {
        foreach ($this->nl as $clauses) {
            if (! empty($clauses)) {
                return true;
            }
        }

        return false;
    }
}

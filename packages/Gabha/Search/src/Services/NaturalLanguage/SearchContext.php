<?php

declare(strict_types=1);

namespace Gabha\Search\Services\NaturalLanguage;

/**
 * Request-scoped carrier for shopper-facing search feedback.
 *
 * The repository records here how it understood a query (the intent chips) and
 * whether it had to relax any inferred filter to avoid an empty page; the Shop
 * products API then reads it back (same request) and attaches it to the JSON so
 * the storefront can tell the shopper "Showing results for: Black · Under ₹300"
 * and, when relaxed, "No exact match — showing the closest results."
 *
 * Bound as a singleton so the value set during getAll() survives to the
 * controller's response within the one request. Stays null for non-search
 * listings (category browsing) so no banner is shown there.
 */
class SearchContext
{
    /**
     * @var array<string, mixed>|null
     */
    protected ?array $feedback = null;

    /**
     * Record the outcome of an NL search for the frontend.
     *
     * @param  string|null  $relaxedTo  CSV of relaxed tiers, e.g. "color,price"
     */
    public function record(SearchIntent $intent, ?string $relaxedTo, int $total): void
    {
        $understood = $this->understoodChips($intent);

        $dropped = $this->droppedLabels($relaxedTo);

        // Nothing worth surfacing — a plain keyword search behaves as before.
        if (empty($understood) && empty($dropped)) {
            $this->feedback = null;

            return;
        }

        $this->feedback = [
            'active'     => true,
            'query'      => $intent->original,
            'understood' => $understood,
            'relaxed'    => ! empty($dropped),
            'dropped'    => $dropped,
            'total'      => $total,
        ];
    }

    /**
     * The feedback payload for the API response, or null when there is nothing
     * to show.
     *
     * @return array<string, mixed>|null
     */
    public function feedback(): ?array
    {
        return $this->feedback;
    }

    /**
     * Human-readable chips describing how the query was understood.
     *
     * @return array<int, string>
     */
    protected function understoodChips(SearchIntent $intent): array
    {
        $chips = [];

        if ($intent->color !== null) {
            $chips[] = $intent->color;
        }

        if ($intent->gender !== null) {
            $chips[] = ucfirst($intent->gender);
        }

        $chips[] = $this->priceChip($intent);

        if ($intent->categorySlug !== null) {
            $chips[] = ucwords(str_replace('-', ' ', $intent->categorySlug));
        }

        return array_values(array_filter($chips));
    }

    /**
     * A readable budget chip ("Under ₹300", "₹300–₹500", "Over ₹200") or null.
     */
    protected function priceChip(SearchIntent $intent): ?string
    {
        $min = $intent->priceMin;
        $max = $intent->priceMax;

        return match (true) {
            $min !== null && $max !== null => '₹'.(int) $min.'–₹'.(int) $max,
            $max !== null                  => 'Under ₹'.(int) $max,
            $min !== null                  => 'Over ₹'.(int) $min,
            default                        => null,
        };
    }

    /**
     * Map the relaxed tiers to shopper-friendly labels.
     *
     * @return array<int, string>
     */
    protected function droppedLabels(?string $relaxedTo): array
    {
        if (empty($relaxedTo)) {
            return [];
        }

        $labels = [
            'color'    => 'colour',
            'price'    => 'price',
            'category' => 'section',
        ];

        return array_values(array_filter(array_map(
            fn ($tier) => $labels[trim($tier)] ?? null,
            explode(',', $relaxedTo)
        )));
    }
}

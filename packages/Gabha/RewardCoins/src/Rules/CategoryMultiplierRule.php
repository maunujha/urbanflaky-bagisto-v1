<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Rules;

use Closure;
use Gabha\RewardCoins\DTOs\CoinEarningPayload;
use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Rules\Contracts\EarningRuleInterface;

/**
 * Applies the highest category coin multiplier matching any of the order's
 * categories by scaling the eligible subtotal (e.g. a 2x category doubles the
 * coins earned on the whole order).
 *
 * Multipliers are stored as a {category_id: multiplier} map on coin_settings.
 */
class CategoryMultiplierRule implements EarningRuleInterface
{
    /**
     * @param  CoinSetting  $settings  Active settings (container-bound to CoinSetting::active()).
     */
    public function __construct(private readonly CoinSetting $settings)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function handle(CoinEarningPayload $payload, Closure $next): CoinEarningPayload|int
    {
        $multiplier = $this->highestMultiplierFor($payload->categoryIds);

        if ($multiplier <= 1.0) {
            return $next($payload);
        }

        return $next(new CoinEarningPayload(
            customerId: $payload->customerId,
            subtotal: $payload->subtotal * $multiplier,
            discountAmount: $payload->discountAmount,
            orderId: $payload->orderId,
            categoryIds: $payload->categoryIds,
            orderIncrementId: $payload->orderIncrementId,
        ));
    }

    /**
     * Highest configured multiplier among the given category ids (1.0 = none).
     *
     * @param  array<int, int>  $categoryIds
     * @return float
     */
    private function highestMultiplierFor(array $categoryIds): float
    {
        $map = (array) ($this->settings->category_coin_multipliers ?? []);

        if ($map === [] || $categoryIds === []) {
            return 1.0;
        }

        $best = 1.0;

        foreach ($categoryIds as $categoryId) {
            // JSON object keys may arrive as int or numeric-string keys.
            $value = $map[$categoryId] ?? $map[(string) $categoryId] ?? null;

            if ($value !== null && (float) $value > $best) {
                $best = (float) $value;
            }
        }

        return $best;
    }
}

<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Rules;

use Closure;
use Gabha\RewardCoins\DTOs\CoinEarningPayload;
use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Rules\Contracts\EarningRuleInterface;

/**
 * When enabled, removes the discounted portion of the order from the subtotal
 * that earns coins (so promo-funded spend doesn't also mint coins).
 */
class ExcludeDiscountedItemsRule implements EarningRuleInterface
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
        if (! $this->settings->exclude_discounted_items) {
            return $next($payload);
        }

        $eligibleSubtotal = max(0.0, $payload->subtotal - $payload->discountAmount);

        return $next(new CoinEarningPayload(
            customerId: $payload->customerId,
            subtotal: $eligibleSubtotal,
            discountAmount: $payload->discountAmount,
            orderId: $payload->orderId,
            categoryIds: $payload->categoryIds,
            orderIncrementId: $payload->orderIncrementId,
        ));
    }
}

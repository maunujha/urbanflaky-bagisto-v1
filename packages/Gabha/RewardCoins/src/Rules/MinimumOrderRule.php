<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Rules;

use Closure;
use Gabha\RewardCoins\DTOs\CoinEarningPayload;
use Gabha\RewardCoins\Models\CoinSetting;
use Gabha\RewardCoins\Rules\Contracts\EarningRuleInterface;

/**
 * Stops the pipeline (zero coins) when the order subtotal is below the
 * configured minimum.
 */
class MinimumOrderRule implements EarningRuleInterface
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
        if ($payload->subtotal < (float) $this->settings->min_order_amount) {
            return 0;
        }

        return $next($payload);
    }
}

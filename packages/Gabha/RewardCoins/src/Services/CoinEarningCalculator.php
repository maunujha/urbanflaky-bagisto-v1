<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Services;

use Gabha\RewardCoins\DTOs\CoinEarningPayload;
use Gabha\RewardCoins\Models\CoinSetting;
use Illuminate\Pipeline\Pipeline;

/**
 * Computes how many coins an order earns.
 *
 * Pure by contract: given the same settings, rules and payload it always
 * returns the same number — it performs no writes and has no side effects. All
 * variable policy lives in the injected rule pipeline; the base conversion is
 * floor(eligibleSubtotal / earning_rate) * coins_per_unit.
 */
class CoinEarningCalculator
{
    /**
     * @param  CoinSetting  $settings  Active settings (container-bound to CoinSetting::active()).
     * @param  array<int, class-string<\Gabha\RewardCoins\Rules\Contracts\EarningRuleInterface>>  $rules
     *         Ordered earning rules, supplied by the service provider so they
     *         can be added/removed without touching this class.
     * @param  Pipeline  $pipeline  Laravel pipeline used to run the rules.
     */
    public function __construct(
        private readonly CoinSetting $settings,
        private readonly array $rules,
        private readonly Pipeline $pipeline,
    ) {
    }

    /**
     * Calculate the coins earned for the given payload.
     *
     * @param  CoinEarningPayload  $payload
     * @return int
     */
    public function calculate(CoinEarningPayload $payload): int
    {
        $result = $this->pipeline
            ->send($payload)
            ->through($this->rules)
            ->then(fn (CoinEarningPayload $eligible): int => $this->toCoins($eligible));

        return max(0, (int) $result);
    }

    /**
     * Convert an eligible subtotal into coins via the base earning formula.
     *
     * @param  CoinEarningPayload  $payload
     * @return int
     */
    private function toCoins(CoinEarningPayload $payload): int
    {
        $rate = (float) $this->settings->earning_rate;

        if ($rate <= 0.0) {
            return 0;
        }

        $units = (int) floor($payload->subtotal / $rate);

        return max(0, $units * (int) $this->settings->coins_per_unit);
    }
}

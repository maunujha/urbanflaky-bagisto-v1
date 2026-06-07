<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Rules\Contracts;

use Closure;
use Gabha\RewardCoins\DTOs\CoinEarningPayload;

/**
 * A single stage in the coin-earning pipeline.
 *
 * Each rule either:
 *   - continues the pipeline by returning `$next($payload)` (optionally passing
 *     a rebuilt, immutable payload to alter the eligible amount), or
 *   - short-circuits by returning an int (e.g. 0) without calling `$next`.
 *
 * New rules are added simply by registering another implementation — existing
 * rules never change (open/closed).
 */
interface EarningRuleInterface
{
    /**
     * @param  CoinEarningPayload  $payload
     * @param  Closure  $next
     * @return CoinEarningPayload|int
     */
    public function handle(CoinEarningPayload $payload, Closure $next): CoinEarningPayload|int;
}

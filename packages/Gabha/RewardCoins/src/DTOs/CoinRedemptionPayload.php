<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\DTOs;

/**
 * Immutable input for the coin-redemption pipeline.
 *
 * Carries a customer's intent to spend coins against an order's value. The
 * redemption service/calculator validates this against the configured caps
 * (per-order ceiling and percentage limit) — no logic lives on the DTO.
 */
final readonly class CoinRedemptionPayload
{
    /**
     * @param  int    $customerId     Owner of the wallet being debited.
     * @param  int    $coinsToRedeem  Number of coins the customer wants to spend.
     * @param  float  $orderSubtotal  Order subtotal the redemption is applied against.
     * @param  int|null  $orderId     Target order id, when already known.
     */
    public function __construct(
        public int $customerId,
        public int $coinsToRedeem,
        public float $orderSubtotal,
        public ?int $orderId = null,
    ) {}
}

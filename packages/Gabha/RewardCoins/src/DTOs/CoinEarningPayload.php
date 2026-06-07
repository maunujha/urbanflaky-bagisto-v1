<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\DTOs;

/**
 * Immutable input for the coin-earning pipeline.
 *
 * Deliberately built from primitives (not a Bagisto Order) so the earning
 * calculator and its rules can be unit-tested in isolation. A mapper/listener
 * is responsible for translating a concrete order into this payload.
 */
final readonly class CoinEarningPayload
{
    /**
     * @param  int                $customerId        Owner of the wallet being credited.
     * @param  float              $subtotal          Order subtotal in store currency (pre-discount).
     * @param  float              $discountAmount    Total discount applied to the order.
     * @param  int                $orderId           Source order id (for traceability).
     * @param  array<int, int>    $categoryIds       Category ids present on the order (for multiplier rules).
     * @param  string             $orderIncrementId  Human-facing order number.
     */
    public function __construct(
        public int $customerId,
        public float $subtotal,
        public float $discountAmount,
        public int $orderId,
        public array $categoryIds,
        public string $orderIncrementId,
    ) {}
}

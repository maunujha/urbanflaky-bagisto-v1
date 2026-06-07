<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\DTOs;

/**
 * Immutable outcome of a coin earning/redemption attempt.
 *
 * Returned by services so callers (listeners, controllers) get a uniform,
 * side-effect-free result they can branch on without inspecting exceptions.
 */
final readonly class TransactionResult
{
    /**
     * @param  bool    $success       Whether coins were moved.
     * @param  int     $coinsAwarded  Net coins credited (or debited, when negative) by the operation.
     * @param  string  $message       Human-readable outcome, safe to surface to the customer.
     */
    public function __construct(
        public bool $success,
        public int $coinsAwarded,
        public string $message,
    ) {}

    /**
     * Build a successful result.
     *
     * @param  int     $coinsAwarded  Net coins moved by the operation.
     * @param  string  $message       Outcome message.
     * @return self
     */
    public static function succeeded(int $coinsAwarded, string $message = ''): self
    {
        return new self(success: true, coinsAwarded: $coinsAwarded, message: $message);
    }

    /**
     * Build a failed (no-op) result.
     *
     * @param  string  $message  Reason the operation did nothing.
     * @return self
     */
    public static function failed(string $message): self
    {
        return new self(success: false, coinsAwarded: 0, message: $message);
    }
}

<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\DTOs;

/**
 * Immutable outcome of a redemption eligibility check.
 *
 * Carries both a machine-readable {@see self::$errorCode} (for the widget JS to
 * branch on, e.g. auto-clamping the slider) and a human-readable
 * {@see self::$message} safe to show the customer. {@see self::$maxCoins} is the
 * amount currently redeemable, so the caller can clamp without a second round-trip.
 */
final readonly class ValidationResult
{
    /**
     * @param  bool    $passes     Whether the requested redemption is allowed.
     * @param  string  $errorCode  Machine-readable failure code ('' on success).
     * @param  string  $message    Human-readable message ('' on success).
     * @param  int     $maxCoins   Coins currently redeemable on this cart.
     */
    public function __construct(
        public bool $passes,
        public string $errorCode,
        public string $message,
        public int $maxCoins = 0,
    ) {}

    /**
     * Build a passing result.
     *
     * @param  int  $maxCoins  Coins currently redeemable on this cart.
     * @return self
     */
    public static function pass(int $maxCoins = 0): self
    {
        return new self(passes: true, errorCode: '', message: '', maxCoins: $maxCoins);
    }

    /**
     * Build a failing result.
     *
     * @param  string  $code      Machine-readable failure code.
     * @param  string  $message   Human-readable reason.
     * @param  int     $maxCoins  Coins currently redeemable (lets the widget clamp).
     * @return self
     */
    public static function fail(string $code, string $message, int $maxCoins = 0): self
    {
        return new self(passes: false, errorCode: $code, message: $message, maxCoins: $maxCoins);
    }
}

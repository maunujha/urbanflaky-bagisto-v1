<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Enums;

/**
 * Lifecycle state of a coin transaction.
 *
 * Coins are granted as `Pending` (held during the post-delivery confirmation
 * window), promoted to `Confirmed` once spendable, and end as `Expired` or
 * `Cancelled` (e.g. order cancellation reverses a pending/confirmed grant).
 * A `Confirmed` transaction of type `Redeemed` (see {@see TransactionType})
 * is reversed to `Reversed` instead (e.g. the order it was spent on is
 * refunded/cancelled after the spend was confirmed).
 */
enum TransactionStatus: string
{
    case Pending   = 'pending';
    case Confirmed = 'confirmed';
    case Expired   = 'expired';
    case Cancelled = 'cancelled';
    case Reversed  = 'reversed';

    /**
     * Human-readable, translated label for display.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending   => trans('reward-coins::reward_coins.transaction.statuses.pending'),
            self::Confirmed => trans('reward-coins::reward_coins.transaction.statuses.confirmed'),
            self::Expired   => trans('reward-coins::reward_coins.transaction.statuses.expired'),
            self::Cancelled => trans('reward-coins::reward_coins.transaction.statuses.cancelled'),
            self::Reversed  => trans('reward-coins::reward_coins.transaction.statuses.reversed'),
        };
    }

    /**
     * Whether coins in this state count toward the spendable balance.
     *
     * @return bool
     */
    public function isSpendable(): bool
    {
        return $this === self::Confirmed;
    }

    /**
     * Whether this is a terminal state that can no longer transition.
     *
     * @return bool
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Expired, self::Cancelled, self::Reversed => true,
            self::Pending, self::Confirmed => false,
        };
    }

    /**
     * All backing values, handy for validation rules and DB constraints.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Enums;

/**
 * The kind of movement a coin transaction represents.
 *
 * Credits add to a wallet (earned, and positive adjustments); debits remove
 * from it (redeemed, expired, reversed). The signed direction is exposed so
 * wallet math never re-implements this classification.
 */
enum TransactionType: string
{
    case Earned   = 'earned';
    case Redeemed = 'redeemed';
    case Expired  = 'expired';
    case Adjusted = 'adjusted';
    case Reversed = 'reversed';

    /**
     * Human-readable, translated label for display.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Earned   => trans('reward-coins::reward_coins.transaction.types.earned'),
            self::Redeemed => trans('reward-coins::reward_coins.transaction.types.redeemed'),
            self::Expired  => trans('reward-coins::reward_coins.transaction.types.expired'),
            self::Adjusted => trans('reward-coins::reward_coins.transaction.types.adjusted'),
            self::Reversed => trans('reward-coins::reward_coins.transaction.types.reversed'),
        };
    }

    /**
     * Whether this type increases a customer's coin balance.
     *
     * Adjustments are operator-driven and may be positive or negative, so they
     * are treated as credits here; the signed amount is decided by the caller.
     *
     * @return bool
     */
    public function isCredit(): bool
    {
        return match ($this) {
            self::Earned, self::Adjusted     => true,
            self::Redeemed, self::Expired, self::Reversed => false,
        };
    }

    /**
     * Whether this type decreases a customer's coin balance.
     *
     * @return bool
     */
    public function isDebit(): bool
    {
        return ! $this->isCredit();
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

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
    case Revoked  = 'revoked';
    case Refunded = 'refunded';
    case Deducted = 'deducted';

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
            self::Revoked  => trans('reward-coins::reward_coins.transaction.types.revoked'),
            self::Refunded => trans('reward-coins::reward_coins.transaction.types.refunded'),
            self::Deducted => trans('reward-coins::reward_coins.transaction.types.deducted'),
        };
    }

    /**
     * Whether this type increases a customer's coin balance.
     *
     * `adjusted` is an admin credit; admin debits are recorded as `deducted`.
     * (Rows written before `deducted` existed used `adjusted` for both
     * directions and remain ambiguous; see docs/REWARD-COINS-ACCOUNTING.md.)
     *
     * @return bool
     */
    public function isCredit(): bool
    {
        return match ($this) {
            self::Earned, self::Adjusted, self::Refunded => true,
            self::Redeemed, self::Expired, self::Reversed, self::Revoked, self::Deducted => false,
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

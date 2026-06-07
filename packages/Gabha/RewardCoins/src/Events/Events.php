<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Events;

/**
 * Canonical names for every event the RewardCoins package fires.
 *
 * Centralising them as constants means listeners, tests and any external
 * integrations reference one source of truth instead of stringly-typed names
 * scattered across the codebase.
 */
final class Events
{
    /**
     * Coins were granted for an order (created in the `pending` state).
     */
    public const COINS_EARNED = 'gabha.reward_coins.earned';

    /**
     * Pending coins matured into a spendable `confirmed` balance.
     */
    public const COINS_CONFIRMED = 'gabha.reward_coins.confirmed';

    /**
     * Coins were spent against an order.
     */
    public const COINS_REDEEMED = 'gabha.reward_coins.redeemed';

    /**
     * A previous grant was reversed (e.g. order cancelled / refunded).
     */
    public const COINS_REVERSED = 'gabha.reward_coins.reversed';

    /**
     * Coins lapsed past their expiry window.
     */
    public const COINS_EXPIRED = 'gabha.reward_coins.expired';

    /**
     * An operator manually adjusted a wallet (credit or debit).
     */
    public const COINS_ADJUSTED = 'gabha.reward_coins.adjusted';

    /**
     * A wallet's balances changed for any reason (catch-all for UI refresh).
     */
    public const WALLET_UPDATED = 'gabha.reward_coins.wallet_updated';

    /**
     * Non-instantiable: this class is a namespace for constants only.
     */
    private function __construct()
    {
    }
}

<?php

/*
|--------------------------------------------------------------------------
| Reward Coins Configuration
|--------------------------------------------------------------------------
|
| Single source of truth for every tunable in the RewardCoins package. No
| magic numbers live in the code — rates, caps and expiry windows are read
| from here (and overridden per-store by the `coin_settings` DB row).
|
| The `is_active` master switch is the package kill-switch: when false the
| service provider still boots, but every listener / earning hook bails out
| early, so the whole feature can be disabled without code changes.
|
*/

return [
    /*
    |--------------------------------------------------------------------------
    | Master Toggle
    |--------------------------------------------------------------------------
    |
    | Hard kill-switch for the entire package. Drive it from .env so the
    | feature can be flipped per-environment. The DB-level `is_active` flag on
    | the coin_settings row is ANDed with this — both must be true to run.
    |
    */
    'is_active' => env('REWARD_COINS_ACTIVE', true),

    /*
    |--------------------------------------------------------------------------
    | Redemption Value
    |--------------------------------------------------------------------------
    |
    | Store-currency value of one coin when redeemed at checkout. Drives
    | CoinRedemptionService::getDiscountValue(). The earning side is governed
    | by the coin_settings row (earning_rate + coins_per_unit), not by this.
    |
    */
    'rupee_per_coin' => (float) env('REWARD_COINS_RUPEE_VALUE', 1),

    /*
    |--------------------------------------------------------------------------
    | Minimum Order Value To Redeem
    |--------------------------------------------------------------------------
    |
    | Floor (store currency) below which coins cannot be redeemed on a cart.
    | Set to 0 to disable the floor. The per-order coverage caps (percentage and
    | absolute ceiling) still live on the coin_settings row; this is a separate,
    | config-only eligibility gate enforced by CoinRedemptionService::validateRedemption().
    |
    */
    'min_order_for_redemption' => (float) env('REWARD_COINS_MIN_ORDER_REDEEM', 200),

    /*
    | Reserved alternate earning knob. The active earning formula uses the
    | per-store coin_settings (earning_rate / coins_per_unit); this is kept for
    | forward-compatibility and is not consumed by CoinEarningCalculator.
    */
    'coins_per_rupee' => (float) env('REWARD_COINS_PER_RUPEE', 1),

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Connection-agnostic queue name every coin listener is pushed onto, so the
    | checkout request is never blocked by coin bookkeeping.
    |
    */
    'queue' => env('REWARD_COINS_QUEUE', 'coins'),

    /*
    |--------------------------------------------------------------------------
    | Order Status Triggers
    |--------------------------------------------------------------------------
    |
    | Bagisto core has no "delivered" status — in this store delivery maps to
    | the `completed` status (set by the Shiprocket webhook / admin). These
    | lists let the listeners react to the right status transitions without
    | hardcoding them. NOTE: the Shiprocket *delivery* path saves the order
    | directly and does not fire `sales.order.update-status.after`; admin status
    | changes (and cancellations via either path) do.
    |
    */
    'confirm_on_statuses' => ['completed'],
    'reverse_on_statuses' => ['canceled', 'closed'],

    /*
    |--------------------------------------------------------------------------
    | View / Asset Namespace
    |--------------------------------------------------------------------------
    |
    | Drives `view('reward-coins::...')` and the `<x-reward-coins::...>` blade
    | component prefix. Keep in sync with the service provider registration.
    |
    */
    'view_namespace' => 'reward-coins',

    /*
    |--------------------------------------------------------------------------
    | Settings Cache
    |--------------------------------------------------------------------------
    |
    | The coin_settings row is read on nearly every coin-aware request, so it
    | is cached. CoinSetting::active() / ::flushCache() honour these values.
    |
    */
    'cache' => [
        'settings_key' => 'reward_coins.settings',
        'settings_ttl' => 300, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | Seed + fallback values, mirrored 1:1 by the coin_settings migration
    | defaults and the CoinSettingsSeeder. CoinSetting::active() falls back to
    | these (as a non-persisted instance) when no row exists yet.
    |
    */
    'defaults' => [
        // Spend threshold (in store currency) that earns one "unit" of coins.
        'earning_rate' => 10.00,

        // Coins granted per earned unit (earning_rate worth of spend).
        'coins_per_unit' => 1,

        // Orders below this subtotal earn nothing.
        'min_order_amount' => 0.00,

        // Hard cap on coin value (store currency) redeemable on a single order.
        'max_redemption_per_order' => 200.00,

        // Cap on the % of an order's value that coins may cover.
        'max_redemption_percent' => 20,

        // Days until earned coins expire.
        'expiry_days' => 365,

        // Days coins stay "pending" before auto-confirming (post delivery window).
        'pending_confirmation_days' => 7,

        // When true, items already discounted do not earn coins.
        'exclude_discounted_items' => false,

        // Map of {category_id: multiplier} applied by CategoryMultiplierRule.
        // Highest matching multiplier wins. Empty = no category bonuses.
        'category_coin_multipliers' => [],

        // Per-store on/off (ANDed with the master `is_active` above).
        'is_active' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Brand Palette
    |--------------------------------------------------------------------------
    |
    | Urbanflaky brand colours, surfaced for any inline style fallback. Blade
    | views prefer the `coins-yellow` / `coins-black` Tailwind utilities.
    |
    */
    'brand' => [
        'coins_yellow' => '#c7eb31',
        'coins_black'  => '#000000',
    ],
];

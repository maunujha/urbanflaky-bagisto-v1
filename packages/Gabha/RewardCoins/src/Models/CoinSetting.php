<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * The single program-wide coin settings row.
 *
 * Read on nearly every coin-aware request, so {@see self::active()} serves a
 * cached singleton instead of hitting the table each time. Mutations must call
 * {@see self::flushCache()} (the seeder and admin settings update both do).
 */
class CoinSetting extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'coin_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'earning_rate',
        'coins_per_unit',
        'min_order_amount',
        'max_redemption_per_order',
        'max_redemption_percent',
        'expiry_days',
        'pending_confirmation_days',
        'exclude_discounted_items',
        'category_coin_multipliers',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'earning_rate'              => 'decimal:2',
        'coins_per_unit'            => 'integer',
        'min_order_amount'          => 'decimal:2',
        'max_redemption_per_order'  => 'decimal:2',
        'max_redemption_percent'    => 'integer',
        'expiry_days'               => 'integer',
        'pending_confirmation_days' => 'integer',
        'exclude_discounted_items'  => 'boolean',
        'category_coin_multipliers' => 'array',
        'is_active'                 => 'boolean',
    ];

    /**
     * Resolve the cached active settings singleton.
     *
     * Falls back to a non-persisted instance built from the config defaults
     * when no row exists yet, so callers always receive usable settings.
     *
     * @return self
     */
    public static function active(): self
    {
        return Cache::remember(
            (string) config('reward_coins.cache.settings_key', 'reward_coins.settings'),
            (int) config('reward_coins.cache.settings_ttl', 300),
            fn (): self => static::query()->first() ?? static::fromDefaults()
        );
    }

    /**
     * Whether the coin program is enabled (master flag AND per-store flag).
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        if (! config('reward_coins.is_active', true)) {
            return false;
        }

        return (bool) static::active()->is_active;
    }

    /**
     * Forget the cached settings singleton.
     *
     * @return void
     */
    public static function flushCache(): void
    {
        Cache::forget((string) config('reward_coins.cache.settings_key', 'reward_coins.settings'));
    }

    /**
     * Build a non-persisted settings instance from the config defaults.
     *
     * @return self
     */
    protected static function fromDefaults(): self
    {
        return new static((array) config('reward_coins.defaults', []));
    }
}

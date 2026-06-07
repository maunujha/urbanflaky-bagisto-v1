<?php

declare(strict_types=1);

namespace Gabha\RewardCoins\Database\Seeders;

use Gabha\RewardCoins\Models\CoinSetting;
use Illuminate\Database\Seeder;

/**
 * Seeds the single coin_settings row from the package config defaults.
 *
 * Idempotent: re-running leaves an existing settings row untouched, so it is
 * safe to call from a fresh install or an upgrade.
 */
class CoinSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        if (CoinSetting::query()->exists()) {
            return;
        }

        CoinSetting::query()->create(
            (array) config('reward_coins.defaults', [])
        );

        CoinSetting::flushCache();
    }
}

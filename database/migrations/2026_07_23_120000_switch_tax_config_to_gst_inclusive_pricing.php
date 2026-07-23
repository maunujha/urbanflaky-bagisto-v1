<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * GST-inclusive pricing switch.
 *
 * The price the admin enters on a product is now the FINAL selling price
 * (MRP, tax included). Bagisto derives the taxable value and GST from it:
 *
 *   taxable = price / (1 + rate/100)     e.g. 199 -> 189.52
 *   gst     = price - taxable            e.g. 199 ->   9.48
 *
 * so what the customer sees on the PDP is exactly what the gateway charges
 * and what the invoice totals to. Display settings are flipped to inclusive
 * across cart and sales so every surface shows the same number.
 *
 * Shipped as a migration (not a manual admin step) so the LIVE store's
 * config flips atomically with the code that renders inclusive prices.
 */
return new class extends Migration
{
    private const CONFIG_KEYS = [
        'sales.taxes.calculation.product_prices',
        'sales.taxes.calculation.shipping_prices',
        'sales.taxes.shopping_cart.display_prices',
        'sales.taxes.shopping_cart.display_subtotal',
        'sales.taxes.shopping_cart.display_shipping_amount',
        'sales.taxes.sales.display_prices',
        'sales.taxes.sales.display_subtotal',
        'sales.taxes.sales.display_shipping_amount',
    ];

    public function up(): void
    {
        $this->setConfig('including_tax');

        /**
         * Catalog-rule prices are floored to whole rupees now (see
         * CatalogRuleProductPrice::calculate) — rebuild the indexed rule
         * prices so ₹199.50-style computed prices become ₹199 immediately.
         *
         * `--mode=full` is REQUIRED: the default 'selective' mode only reindexes
         * products flagged as changed, so it rebuilds nothing here and leaves the
         * displayed price (product_price_indices, read by both PDP and cart) stale
         * at its pre-floor value. A full reindex recomputes every product from the
         * now-floored rule prices. Best-effort: the daily schedule also rebuilds.
         */
        try {
            Artisan::call('product:price-rule:index');
            Artisan::call('indexer:index', ['--type' => ['price'], '--mode' => ['full']]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function down(): void
    {
        $this->setConfig('excluding_tax');
    }

    private function setConfig(string $value): void
    {
        foreach (self::CONFIG_KEYS as $code) {
            DB::table('core_config')->updateOrInsert(
                ['code' => $code, 'channel_code' => null, 'locale_code' => null],
                ['value' => $value, 'updated_at' => now()],
            );
        }
    }
};

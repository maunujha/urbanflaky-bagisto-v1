<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coin_settings', function (Blueprint $table) {
            $table->id();

            // Spend threshold (store currency) that earns one unit of coins.
            $table->decimal('earning_rate', 10, 2)->default(10.00);

            // Coins granted per earned unit.
            $table->unsignedInteger('coins_per_unit')->default(1);

            // Orders below this subtotal earn nothing.
            $table->decimal('min_order_amount', 10, 2)->default(0);

            // Hard cap on coin value redeemable per order (store currency).
            $table->decimal('max_redemption_per_order', 10, 2)->default(200.00);

            // Cap on the % of an order's value coins may cover.
            $table->unsignedSmallInteger('max_redemption_percent')->default(20);

            // Days until earned coins expire.
            $table->unsignedSmallInteger('expiry_days')->default(365);

            // Days coins stay pending before auto-confirming.
            $table->unsignedSmallInteger('pending_confirmation_days')->default(7);

            // When true, already-discounted items do not earn coins.
            $table->boolean('exclude_discounted_items')->default(false);

            // Map of {category_id: multiplier} for CategoryMultiplierRule.
            $table->json('category_coin_multipliers')->nullable();

            // Per-store on/off switch (ANDed with the master config flag).
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_settings');
    }
};

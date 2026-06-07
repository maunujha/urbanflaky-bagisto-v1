<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records how many coins were spent on each order.
 *
 * The rupee value of the redemption is already folded into the order's
 * grand_total / discount_amount (by the cart-total collector); this column
 * stores the coin *count* so the value is auditable and reversible without
 * touching any Bagisto core table or migration.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'coins_redeemed')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            // Coins spent on this order. Rupee value = coins_redeemed * rupee_per_coin.
            $table->unsignedInteger('coins_redeemed')->default(0)->after('applied_cart_rule_ids');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'coins_redeemed')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('coins_redeemed');
        });
    }
};

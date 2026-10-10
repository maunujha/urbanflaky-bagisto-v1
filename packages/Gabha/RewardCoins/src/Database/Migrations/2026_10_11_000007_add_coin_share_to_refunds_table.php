<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records, per refund (credit memo), the part of the order's cart-level coin
 * discount attributed to what was refunded.
 *
 *  - coins_refunded             Coins handed back for this refund. NULL marks
 *                               a refund created before this column existed
 *                               (its cash total ignored the coin discount).
 *  - base_coin_discount_amount  The same share in base currency; included in
 *                               the refund's discount, so the cash refunded
 *                               excludes the value paid with coins.
 *
 * Additive only; existing refunds are left NULL / 0.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->unsignedInteger('coins_refunded')->nullable()->after('base_discount_amount');
            $table->decimal('base_coin_discount_amount', 12, 4)->default(0)->after('coins_refunded');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropColumn(['coins_refunded', 'base_coin_discount_amount']);
        });
    }
};

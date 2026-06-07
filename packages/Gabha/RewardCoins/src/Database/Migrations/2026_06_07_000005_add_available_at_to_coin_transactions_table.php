<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the post-delivery return-window stamp to the coin ledger.
 *
 * Earned coins stay `pending` after delivery until `available_at` passes (the
 * store's return window, driven by coin_settings.pending_confirmation_days),
 * at which point `reward-coins:confirm-available` promotes them to spendable.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('coin_transactions', function (Blueprint $table) {
            // When a pending earned batch becomes spendable (set on delivery).
            $table->timestamp('available_at')->nullable()->after('expires_at');

            // Drives the daily confirm-available sweep.
            $table->index(['status', 'available_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coin_transactions', function (Blueprint $table) {
            $table->dropIndex(['status', 'available_at']);
            $table->dropColumn('available_at');
        });
    }
};

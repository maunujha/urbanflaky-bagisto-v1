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
        Schema::create('customer_coin_wallets', function (Blueprint $table) {
            $table->id();

            // One wallet per customer. Matches customers.id (unsigned int).
            $table->unsignedInteger('customer_id')->unique();

            // Spendable, confirmed balance.
            $table->unsignedInteger('balance')->default(0);

            // Coins awaiting confirmation (not yet spendable).
            $table->unsignedInteger('pending_balance')->default(0);

            // Running totals for lifetime reporting.
            $table->unsignedInteger('lifetime_earned')->default(0);
            $table->unsignedInteger('lifetime_redeemed')->default(0);

            $table->timestamps();

            $table->index('customer_id');

            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_coin_wallets');
    }
};

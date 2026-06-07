<?php

use Gabha\RewardCoins\Enums\TransactionStatus;
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
        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->id();

            // Wallet owner. Matches customers.id (unsigned int).
            $table->unsignedInteger('customer_id');

            // TransactionType value (earned, redeemed, expired, adjusted, reversed).
            $table->string('type');

            // TransactionStatus value; new grants start pending.
            $table->string('status')->default(TransactionStatus::Pending->value);

            // Always a positive magnitude; direction is implied by `type`.
            $table->unsignedInteger('amount');

            // Source order, when applicable. Matches orders.id (unsigned int).
            $table->unsignedInteger('order_id')->nullable();

            // Free-form audit note.
            $table->text('note')->nullable();

            // When this (earned) batch lapses.
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            // Hot paths: balance recomputation by status, history filtering by type.
            $table->index(['customer_id', 'status']);
            $table->index(['customer_id', 'type']);

            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('cascade');

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_transactions');
    }
};

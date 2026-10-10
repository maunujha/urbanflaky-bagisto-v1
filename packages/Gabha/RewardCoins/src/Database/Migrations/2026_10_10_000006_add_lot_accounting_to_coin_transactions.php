<?php

use Gabha\RewardCoins\Enums\TransactionStatus;
use Gabha\RewardCoins\Enums\TransactionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Batch-level ("lot") accounting for reward coins.
 *
 * Purely additive: no existing column, row or wallet balance is rewritten.
 *
 *  - coin_transactions.remaining      Unspent coins left in a credit lot
 *                                     (earned / refunded / adjusted credits).
 *                                     NULL on debit rows and on legacy lots
 *                                     whose history has not been backfilled.
 *  - coin_transactions.operation_key  Stable idempotency key for one logical
 *                                     operation; UNIQUE so retries, duplicate
 *                                     events and concurrent workers can never
 *                                     post the same movement twice.
 *  - coin_transactions.meta           Structured audit context (refund ratio,
 *                                     unrecovered claw-back, source lot...).
 *  - coin_allocations                 Which lot(s) each debit consumed, and how
 *                                     much of that was later restored.
 *
 * The only data touched is a provable backfill: still-PENDING earned rows get
 * remaining = amount. Pending coins have never been spendable, so none of them
 * can have been spent. Confirmed legacy lots are left NULL on purpose; see the
 * `reward-coins:backfill-lots` command (dry-run by default).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('coin_transactions', function (Blueprint $table) {
            $table->unsignedInteger('remaining')->nullable()->after('amount');
            $table->string('operation_key', 191)->nullable()->unique()->after('remaining');
            $table->json('meta')->nullable()->after('note');

            // FIFO lot selection per customer (soonest-expiring first).
            $table->index(['customer_id', 'status', 'expires_at'], 'coin_txn_customer_lots_idx');

            // Nightly expiry sweep.
            $table->index(['type', 'status', 'expires_at'], 'coin_txn_expiry_idx');

            // Per-order reversal / refund lookups (type first so the FK keeps its own index).
            $table->index(['type', 'order_id'], 'coin_txn_order_type_idx');
        });

        Schema::create('coin_allocations', function (Blueprint $table) {
            $table->id();

            // The debit (redeemed / revoked / expired / deducted) row.
            $table->foreignId('debit_transaction_id')
                ->constrained('coin_transactions')
                ->cascadeOnDelete();

            // The lot it consumed. NULL = legacy, un-backfilled balance.
            $table->foreignId('credit_transaction_id')
                ->nullable()
                ->constrained('coin_transactions')
                ->nullOnDelete();

            $table->unsignedInteger('amount');

            // Coins of this allocation later handed back (redemption restores).
            $table->unsignedInteger('restored')->default(0);

            $table->timestamps();
        });

        DB::table('coin_transactions')
            ->where('type', TransactionType::Earned->value)
            ->where('status', TransactionStatus::Pending->value)
            ->whereNull('remaining')
            ->update(['remaining' => DB::raw('amount')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_allocations');

        Schema::table('coin_transactions', function (Blueprint $table) {
            $table->dropIndex('coin_txn_customer_lots_idx');
            $table->dropIndex('coin_txn_expiry_idx');
            $table->dropIndex('coin_txn_order_type_idx');
            $table->dropUnique(['operation_key']);
            $table->dropColumn(['remaining', 'operation_key', 'meta']);
        });
    }
};

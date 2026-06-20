<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * An append-only ledger of every inventory change. Kept generic
     * (type + reference) so future modules (sales, returns, manual
     * adjustments) can record movements without schema changes. Product /
     * source links are soft references (indexed, no FK) so the audit trail is
     * immutable even if a product or source is later removed.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('product_id');
            $table->unsignedInteger('inventory_source_id');

            /* Reason for the movement, e.g. "purchase". */
            $table->string('type')->default('purchase');

            /* Signed delta (+ for inbound stock) plus before/after for audit. */
            $table->integer('quantity');
            $table->integer('qty_before');
            $table->integer('qty_after');

            /* Polymorphic-style origin, e.g. reference_type=purchase, id=12. */
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index(['reference_type', 'reference_id']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Materialised per-variant inventory aggregate. The source of truth is the
     * `stock_movements` ledger; this row is recomputed after every movement
     * (and every purchase) by InventoryService — it is never edited directly.
     */
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();

            /* Soft reference to products.id (the variant). One row per variant. */
            $table->unsignedInteger('product_variant_id');

            $table->integer('current_stock')->default(0);
            $table->decimal('average_cost', 12, 4)->default(0);
            $table->decimal('inventory_value', 14, 4)->default(0);

            $table->timestamps();

            /* Enforce a single inventory record per variant (also indexes it). */
            $table->unique('product_variant_id');

            /* Backs the low-stock filter and stock sort on the list screen. */
            $table->index('current_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};

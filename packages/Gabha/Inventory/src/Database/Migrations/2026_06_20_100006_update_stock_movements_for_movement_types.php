<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Brings the Module 2 `stock_movements` table up to the Module 4 spec:
     * a typed, numbered movement ledger. `product_id`→`product_variant_id` and
     * `type`→`movement_type` keep their existing indexes (rename preserves them);
     * `inventory_source_id` is dropped (source is a storefront concern handled in
     * the service); `movement_number` and a `created_at` index are added.
     */
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->renameColumn('product_id', 'product_variant_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->renameColumn('type', 'movement_type');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn('inventory_source_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('movement_number')->after('id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unique('movement_number');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropUnique(['movement_number']);
            $table->dropIndex(['created_at']);
            $table->dropColumn('movement_number');
            $table->unsignedInteger('inventory_source_id')->default(1);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->renameColumn('product_variant_id', 'product_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->renameColumn('movement_type', 'type');
        });
    }
};

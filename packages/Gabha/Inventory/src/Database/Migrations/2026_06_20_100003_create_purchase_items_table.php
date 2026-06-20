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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('purchase_id');

            /*
             * A "product variant" is a Bagisto product row (products.id is an
             * unsigned INTEGER). This is a SOFT reference (indexed, no FK): a
             * purchase is historical/ledger data and must survive — and must not
             * block — later catalog product deletion.
             */
            $table->unsignedInteger('product_variant_id');

            $table->integer('quantity');
            $table->decimal('unit_cost', 12, 4);
            $table->decimal('total_cost', 12, 4);

            $table->timestamps();

            $table->foreign('purchase_id')
                ->references('id')
                ->on('purchases')
                ->onDelete('cascade');

            $table->index('purchase_id');
            $table->index('product_variant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};

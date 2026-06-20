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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();

            /* Human-readable, auto-generated reference, e.g. PUR-2026-000001. */
            $table->string('purchase_number')->unique();

            /*
             * vendors.id is a bigInteger (->id()), so vendor_id is bigInteger.
             * RESTRICT on delete is the DB-level backstop for the Module 1 rule
             * "a vendor with purchase records cannot be deleted".
             */
            $table->unsignedBigInteger('vendor_id');

            $table->date('purchase_date');
            $table->string('invoice_number')->nullable();
            $table->string('bill_file')->nullable();

            $table->unsignedInteger('total_quantity')->default(0);
            $table->decimal('total_amount', 12, 4)->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->onDelete('restrict');

            /* Indexes backing the datagrid filters / sorts and the vendor join. */
            $table->index('vendor_id');
            $table->index('purchase_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};

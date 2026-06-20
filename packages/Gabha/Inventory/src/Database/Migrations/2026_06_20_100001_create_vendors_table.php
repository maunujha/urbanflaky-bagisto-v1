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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            /*
             * A vendor's mobile number is the natural business key, so it is
             * unique. The unique constraint also creates the supporting index,
             * which serves the uniqueness check on every create / update.
             */
            $table->string('mobile')->unique();
            $table->text('address');
            $table->timestamps();

            /* Indexes backing the datagrid's default sort/search columns. */
            $table->index('name');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};

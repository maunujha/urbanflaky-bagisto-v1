<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fulfillment: flags a product as "Made on Demand" (produced only after an order
 * is placed — e.g. print-on-demand). A native column keeps the flag queryable and
 * reusable across models, repositories, APIs and future fulfillment features.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'is_made_on_demand')) {
                $table->boolean('is_made_on_demand')->default(false)->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'is_made_on_demand')) {
                $table->dropColumn('is_made_on_demand');
            }
        });
    }
};

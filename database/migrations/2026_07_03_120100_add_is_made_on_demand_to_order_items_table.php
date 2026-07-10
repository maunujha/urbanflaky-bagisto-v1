<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persist the "Made on Demand" fulfillment flag on the order item at purchase time
 * so the order retains the correct fulfillment type even if the product is later
 * edited or its flag is toggled off. Foundation for future production/fulfillment
 * workflows and reporting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'is_made_on_demand')) {
                $table->boolean('is_made_on_demand')->default(false)->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'is_made_on_demand')) {
                $table->dropColumn('is_made_on_demand');
            }
        });
    }
};

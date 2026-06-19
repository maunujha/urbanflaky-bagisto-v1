<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storage for natural-language search analytics.
 *
 * One row per storefront search: the raw term, the parsed intent, the resulting
 * filter expression and how many products matched (plus whether relaxation had
 * to rescue it). This is the data behind `urbanflaky:search:analytics` — top
 * searches, zero-result terms and facet usage for merchandising.
 *
 * The table is additive and independent of the search path: dropping it (or
 * setting analytics.driver to 'log'/'null') leaves search untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();

            $table->string('term')->index();
            $table->string('clean_query')->nullable();

            // Parsed intent facets.
            $table->string('color')->nullable();
            $table->decimal('price_min', 12, 4)->nullable();
            $table->decimal('price_max', 12, 4)->nullable();
            $table->string('gender')->nullable();
            $table->string('product_type')->nullable();
            $table->string('category_slug')->nullable();
            $table->boolean('had_intent')->default(false);

            // Outcome.
            $table->text('filters')->nullable();
            $table->unsignedInteger('results_count')->default(0)->index();
            $table->string('relaxed_to')->nullable();

            // Context.
            $table->string('channel')->nullable();
            $table->string('locale')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable()->index();

            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};

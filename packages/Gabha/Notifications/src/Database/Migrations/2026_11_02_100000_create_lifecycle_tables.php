<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* Append-only record of every marketing-consent change: proof of who agreed, when, where and how. */
        Schema::create('marketing_consents', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->unsignedInteger('customer_id')->nullable()->index();
            $table->string('action', 32);
            $table->string('source', 96);
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('order_feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id')->unique();
            $table->string('email');
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });

        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            /* The exact sellable product: a variant for configurable products. */
            $table->unsignedInteger('product_id');
            $table->string('email');
            $table->unsignedInteger('customer_id')->nullable();
            $table->string('status', 16)->default('pending');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'product_id']);
            $table->unique(['product_id', 'email', 'status']);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
        Schema::dropIfExists('order_feedback');
        Schema::dropIfExists('marketing_consents');
    }
};

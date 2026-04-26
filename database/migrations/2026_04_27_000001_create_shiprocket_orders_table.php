<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shiprocket_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id')->unique();
            $table->string('shiprocket_order_id')->nullable();
            $table->string('shipment_id')->nullable();
            $table->string('awb_code')->nullable();
            $table->string('courier_name')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shiprocket_orders');
    }
};

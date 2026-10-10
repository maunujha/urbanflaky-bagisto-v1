<?php

use App\Services\Shiprocket\ShipmentStage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Turns shiprocket_orders from a "pushed?" marker into the full fulfilment
 * record: courier, every milestone timestamp, the last API error, and cached
 * document links. `status` now holds a normalised stage code (see
 * App\Services\Shiprocket\ShipmentStage); the courier's raw wording goes to
 * `current_status`. Status history lives in shiprocket_tracking_events.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shiprocket_orders', function (Blueprint $table) {
            $table->string('courier_company_id')->nullable()->after('courier_name');
            $table->string('current_status')->nullable()->after('status');
            $table->boolean('is_cod')->default(false)->after('current_status');
            $table->unsignedInteger('push_attempts')->default(0)->after('is_cod');
            $table->text('last_error')->nullable()->after('push_attempts');
            $table->timestamp('last_error_at')->nullable()->after('last_error');
            $table->timestamp('pushed_at')->nullable();
            $table->timestamp('awb_assigned_at')->nullable();
            $table->timestamp('pickup_scheduled_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('last_event_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->date('etd')->nullable();
            $table->string('label_url', 1024)->nullable();
            $table->string('manifest_url', 1024)->nullable();
            $table->string('invoice_url', 1024)->nullable();

            $table->index('awb_code');
            $table->index('status');
        });

        DB::table('shiprocket_orders')->update(['awb_code' => DB::raw("NULLIF(awb_code, '')")]);

        /* Legacy rows hold raw courier wording ("created", "IN TRANSIT",
           "Delivered"…). Convert each to its stage code and keep the wording in
           current_status; "created" and anything unrecognised mean "pushed,
           nothing since". */
        foreach (DB::table('shiprocket_orders')->get(['id', 'status']) as $row) {
            $stage = ShipmentStage::fromStatus($row->status) ?? ShipmentStage::NEW;

            DB::table('shiprocket_orders')->where('id', $row->id)->update([
                'status'         => $stage,
                'current_status' => strcasecmp((string) $row->status, 'created') === 0 ? null : $row->status,
                'delivered_at'   => $stage === ShipmentStage::DELIVERED ? DB::raw('updated_at') : null,
            ]);
        }

        Schema::create('shiprocket_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id');
            $table->string('stage')->nullable();
            $table->string('status')->nullable();
            $table->string('activity', 500)->nullable();
            $table->string('location')->nullable();
            $table->timestamp('event_at')->nullable();
            $table->string('source', 20);
            $table->char('fingerprint', 40);
            $table->timestamps();

            $table->unique(['order_id', 'fingerprint']);
            $table->index(['order_id', 'event_at']);

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shiprocket_tracking_events');

        Schema::table('shiprocket_orders', function (Blueprint $table) {
            $table->dropIndex(['awb_code']);
            $table->dropIndex(['status']);

            $table->dropColumn([
                'courier_company_id', 'current_status', 'is_cod', 'push_attempts',
                'last_error', 'last_error_at', 'pushed_at', 'awb_assigned_at',
                'pickup_scheduled_at', 'shipped_at', 'delivered_at', 'last_event_at',
                'last_synced_at', 'etd', 'label_url', 'manifest_url', 'invoice_url',
            ]);
        });
    }
};

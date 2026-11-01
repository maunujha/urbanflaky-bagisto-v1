<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * The "inventory source" email copies every shipment to the warehouse contact,
 * which here is the store's own mailbox: pure noise against the Hostinger quota.
 * One-time switch-off; the admin toggle still works afterwards.
 */
return new class extends Migration
{
    protected string $code = 'emails.general.notifications.emails.general.notifications.new_inventory_source';

    public function up(): void
    {
        DB::table('core_config')->where('code', $this->code)->update(['value' => '0']);
    }

    public function down(): void
    {
        DB::table('core_config')->where('code', $this->code)->update(['value' => '1']);
    }
};

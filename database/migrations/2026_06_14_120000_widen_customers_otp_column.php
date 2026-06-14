<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Login / registration OTP is a 6-digit code: random_int(100000, 999999).
     * But `customers.otp` was created (manually, no prior migration) as varchar(4),
     * so with MySQL not in STRICT mode the 6-digit code was silently TRUNCATED to
     * 4 chars on save. The customer received the full code by SMS, but verify
     * (hash_equals on the stored value) could never match -> "Invalid OTP".
     * Widen the column to hold the full code (with a little headroom).
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('otp', 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('otp', 4)->nullable()->change();
        });
    }
};

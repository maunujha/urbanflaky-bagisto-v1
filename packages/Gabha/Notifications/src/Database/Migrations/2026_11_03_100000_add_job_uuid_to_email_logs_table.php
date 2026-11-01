<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The failed_jobs row behind a failed email, so admin can retry that exact
     * job. Also indexes Bagisto's subscriber email, which every newsletter
     * sign-up and marketing consent check looks up.
     */
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->uuid('job_uuid')->nullable()->after('error');
        });

        if (! Schema::hasIndex('subscribers_list', ['email'])) {
            Schema::table('subscribers_list', function (Blueprint $table) {
                $table->index('email');
            });
        }
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropColumn('job_uuid');
        });

        Schema::table('subscribers_list', function (Blueprint $table) {
            $table->dropIndex(['email']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 64)->index();
            $table->string('category', 16)->default('transactional')->index();
            $table->string('mailable');
            $table->string('recipient')->index();
            $table->string('subject')->nullable();
            $table->string('status', 16)->index();
            /* One email per key, ever: retries and replayed webhooks stop here. */
            $table->string('dedupe_key', 191)->nullable()->unique();
            $table->nullableMorphs('related');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['category', 'created_at']);
        });

        Schema::create('email_suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('scope', 32);
            $table->string('reason', 64)->default('unsubscribed');
            $table->timestamps();

            $table->unique(['email', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_suppressions');
        Schema::dropIfExists('email_logs');
    }
};

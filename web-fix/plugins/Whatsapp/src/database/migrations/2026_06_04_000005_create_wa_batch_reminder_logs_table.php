<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wa_batch_reminder_logs')) {
            return;
        }

        Schema::create('wa_batch_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id')->index();
            $table->string('batch_type', 32);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('event_key', 64);
            $table->string('recipient_type', 32)->nullable();
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('label')->nullable();
            $table->string('status', 16);
            $table->string('skip_reason')->nullable();
            $table->json('result_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_batch_reminder_logs');
    }
};

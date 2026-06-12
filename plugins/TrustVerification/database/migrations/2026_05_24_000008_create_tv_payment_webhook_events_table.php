<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tv_payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32)->default('cashfree');
            $table->string('event_id', 120)->nullable();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('payment_transaction_id')->nullable()->index();
            $table->string('signature_hash', 64)->nullable();
            $table->string('payload_hash', 64);
            $table->string('payment_status', 32)->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('rejected_reason', 120)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique('payload_hash');
            $table->index(['provider', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_payment_webhook_events');
    }
};

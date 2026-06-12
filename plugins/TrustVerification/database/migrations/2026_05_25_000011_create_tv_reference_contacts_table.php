<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tv_reference_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('tv_orders')->cascadeOnDelete();
            $table->string('reference_type', 40);
            $table->string('name', 120);
            $table->string('relation', 80)->nullable();
            $table->string('mobile', 20);
            $table->string('email', 190)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('pending');
            $table->text('admin_notes')->nullable();
            $table->string('call_outcome', 500)->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_status_at')->nullable();
            $table->string('external_ref_id', 64)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['order_id', 'reference_type']);
            $table->index('external_ref_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_reference_contacts');
    }
};

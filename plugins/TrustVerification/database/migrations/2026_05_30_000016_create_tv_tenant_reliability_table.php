<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tv_tenant_reliability', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->unique();
            $table->unsignedTinyInteger('verification_completion')->default(0);
            $table->string('reliability_level', 32)->default('basic_tenant')->index();
            $table->string('verification_status', 24)->default('pending')->index();
            $table->boolean('public_visible')->default(true);
            $table->json('summary_json')->nullable();
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->index(['public_visible', 'verification_completion'], 'tv_rel_pub_completion_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_tenant_reliability');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issued Sukoon verification badges (SVO / SVT numbers).
 *
 * Note: tv_trust_badges remains the trust-score catalog from migration 000014.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tv_verification_badges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('order_id')->unique();
            $table->string('badge_type', 16); // owner | tenant
            $table->string('badge_number', 32)->unique();
            $table->string('status', 24)->default('pending'); // pending, verified, expired, revoked
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoke_reason', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['badge_type', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_verification_badges');
    }
};

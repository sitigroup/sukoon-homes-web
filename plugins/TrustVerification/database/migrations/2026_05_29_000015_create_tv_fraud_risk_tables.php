<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tv_risk_signals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->string('signal_type', 64)->index();
            $table->unsignedTinyInteger('risk_points')->default(0);
            $table->string('source', 16)->default('auto')->index();
            $table->text('notes')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'signal_type', 'source']);
            $table->index(['customer_id', 'created_at']);
        });

        Schema::create('tv_risk_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->unique();
            $table->unsignedTinyInteger('risk_score')->default(0);
            $table->string('risk_level', 16)->default('low')->index();
            $table->smallInteger('manual_override')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->index('risk_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_risk_profiles');
        Schema::dropIfExists('tv_risk_signals');
    }
};

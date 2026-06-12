<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tv_orders', function (Blueprint $table) {
            $table->boolean('consent_given')->default(false)->after('requester_notes');
            $table->text('consent_text')->nullable()->after('consent_given');
            $table->string('consent_ip', 45)->nullable()->after('consent_text');
            $table->text('consent_user_agent')->nullable()->after('consent_ip');
            $table->timestamp('consent_given_at')->nullable()->after('consent_user_agent');
            $table->string('legal_version', 32)->nullable()->default('2026-05-24')->after('consent_given_at');
        });

        Schema::create('tv_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('action', 80)->index();
            $table->text('description')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('order_id')->references('id')->on('tv_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_audit_logs');

        Schema::table('tv_orders', function (Blueprint $table) {
            $table->dropColumn([
                'consent_given',
                'consent_text',
                'consent_ip',
                'consent_user_agent',
                'consent_given_at',
                'legal_version',
            ]);
        });
    }
};

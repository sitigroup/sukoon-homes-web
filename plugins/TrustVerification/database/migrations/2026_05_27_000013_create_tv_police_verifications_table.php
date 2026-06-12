<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tv_police_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('tv_orders')->cascadeOnDelete();
            $table->string('provider', 40)->default('rajasthan_police');
            $table->string('verification_type', 32)->default('tenant');
            $table->string('status', 32)->default('not_submitted');
            $table->string('police_station_name', 160)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('district', 80)->nullable();
            $table->string('state', 80)->default('Rajasthan');
            $table->string('applicant_mobile', 20)->nullable();
            $table->string('reference_number', 64)->nullable();
            $table->string('acknowledgement_document_path', 500)->nullable();
            $table->string('acknowledgement_original_name', 255)->nullable();
            $table->string('acknowledgement_mime', 120)->nullable();
            $table->unsignedInteger('acknowledgement_size')->nullable();
            $table->string('certificate_document_path', 500)->nullable();
            $table->string('certificate_original_name', 255)->nullable();
            $table->string('certificate_mime', 120)->nullable();
            $table->unsignedInteger('certificate_size')->nullable();
            $table->string('status_check_url', 500)->nullable();
            $table->string('provider_reference_number', 64)->nullable();
            $table->string('provider_status', 64)->nullable();
            $table->timestamp('provider_last_checked_at')->nullable();
            $table->json('provider_raw_response')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('officer_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('customer_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('reference_number');
            $table->index('provider_reference_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_police_verifications');
    }
};

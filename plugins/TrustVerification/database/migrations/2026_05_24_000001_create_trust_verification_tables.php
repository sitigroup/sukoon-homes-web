<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tv_packages', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20); // tenant | owner
            $table->string('city_slug', 80)->default('barmer');
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('price');
            $table->string('currency', 8)->default('INR');
            $table->unsignedSmallInteger('delivery_hours')->default(72);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['type', 'city_slug', 'is_active']);
        });

        Schema::create('tv_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 32)->unique();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('package_id')->index();
            $table->string('order_type', 20); // tenant | owner
            $table->string('city_slug', 80)->default('barmer');
            $table->string('requester_name')->nullable();
            $table->string('requester_email')->nullable();
            $table->string('requester_phone', 20)->nullable();
            $table->string('status', 30)->default('submitted');
            $table->string('payment_status', 20)->default('pending');
            $table->unsignedInteger('amount');
            $table->text('requester_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('package_id')->references('id')->on('tv_packages')->restrictOnDelete();
        });

        Schema::create('tv_subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->unique();
            $table->string('subject_type', 20);
            $table->string('full_name');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->text('current_address')->nullable();
            $table->text('permanent_address')->nullable();
            $table->string('property_address')->nullable();
            $table->string('id_type', 40)->nullable();
            $table->string('id_number_hint', 64)->nullable();
            $table->string('employment_company')->nullable();
            $table->string('employment_role')->nullable();
            $table->boolean('consent_given')->default(false);
            $table->timestamp('consent_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('tv_orders')->cascadeOnDelete();
        });

        Schema::create('tv_check_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->string('check_key', 60);
            $table->string('label');
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('tv_orders')->cascadeOnDelete();
            $table->unique(['order_id', 'check_key']);
        });

        Schema::create('tv_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->unique();
            $table->string('file_path')->nullable();
            $table->string('risk_level', 20)->nullable();
            $table->text('summary')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('tv_orders')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_reports');
        Schema::dropIfExists('tv_check_items');
        Schema::dropIfExists('tv_subjects');
        Schema::dropIfExists('tv_orders');
        Schema::dropIfExists('tv_packages');
    }
};

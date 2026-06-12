<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tv_sample_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type', 16);
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->string('city_slug', 80)->nullable();
            $table->foreignId('package_id')->nullable()->constrained('tv_packages')->nullOnDelete();
            $table->string('file_path')->nullable();
            $table->string('original_filename', 255)->nullable();
            $table->unsignedInteger('file_size_bytes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['report_type', 'is_active']);
            $table->index(['report_type', 'city_slug', 'is_active']);
            $table->index(['report_type', 'package_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_sample_reports');
    }
};

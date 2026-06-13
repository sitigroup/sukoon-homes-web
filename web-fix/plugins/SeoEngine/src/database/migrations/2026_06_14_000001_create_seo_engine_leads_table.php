<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_engine_leads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('phone', 32);
            $table->string('requirement', 500)->nullable();
            $table->string('source_path', 512)->index();
            $table->unsignedBigInteger('area_id')->nullable()->index();
            $table->unsignedBigInteger('sub_area_id')->nullable();
            $table->string('form_type', 32)->default('lead')->index();
            $table->string('status', 16)->default('new')->index();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_engine_leads');
    }
};

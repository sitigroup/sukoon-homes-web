<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_engine_template_versions', function (Blueprint $table) {
            $table->id();
            $table->string('page_type', 64)->index();
            $table->string('title_template', 512);
            $table->string('h1_template', 512);
            $table->text('meta_description_template')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_engine_template_versions');
    }
};

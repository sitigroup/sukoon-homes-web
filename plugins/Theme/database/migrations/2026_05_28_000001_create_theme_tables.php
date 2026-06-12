<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_settings', function (Blueprint $table) {
            $table->id();
            $table->json('draft_payload')->nullable();
            $table->json('published_payload')->nullable();
            $table->string('active_preset', 64)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->timestamps();
        });

        Schema::create('theme_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('version_number');
            $table->json('payload');
            $table->string('preset_slug', 64)->nullable();
            $table->string('action', 32);
            $table->string('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('version_number');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_versions');
        Schema::dropIfExists('theme_settings');
    }
};

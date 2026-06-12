<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tv_content_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('content_key', 120)->unique();
            $table->string('group_key', 40)->index();
            $table->string('title', 190);
            $table->string('type', 32)->default('text');
            $table->longText('content')->nullable();
            $table->json('content_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_content_blocks');
    }
};

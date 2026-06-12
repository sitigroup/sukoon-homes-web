<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_engine_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 191)->unique();
            $table->json('value')->nullable();
            $table->string('group', 64)->default('general')->index();
            $table->timestamps();
        });

        Schema::create('seo_engine_pages', function (Blueprint $table) {
            $table->id();
            $table->string('path', 512)->unique();
            $table->string('page_type', 64)->index();
            $table->json('params')->nullable();
            $table->string('title', 512)->nullable();
            $table->string('h1', 512)->nullable();
            $table->text('meta_description')->nullable();
            $table->longText('intro_html')->nullable();
            $table->json('faq_json')->nullable();
            $table->unsignedInteger('listing_count')->default(0);
            $table->unsignedTinyInteger('quality_score')->nullable();
            $table->boolean('is_indexable')->default(false)->index();
            $table->boolean('lock_content')->default(false);
            $table->timestamp('content_generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('seo_engine_slug_history', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 64)->index();
            $table->unsignedBigInteger('entity_id')->index();
            $table->string('old_slug', 255);
            $table->string('new_slug', 255);
            $table->timestamp('changed_at')->useCurrent();
            $table->timestamps();
            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('seo_engine_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path', 512)->unique();
            $table->string('to_path', 512);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamps();
        });

        Schema::create('seo_engine_locality_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('area_id')->index();
            $table->unsignedBigInteger('sub_area_id')->nullable()->index();
            $table->string('period', 16)->index();
            $table->unsignedInteger('avg_rent')->nullable();
            $table->unsignedInteger('min_rent')->nullable();
            $table->unsignedInteger('max_rent')->nullable();
            $table->unsignedInteger('listing_count')->default(0);
            $table->json('bhk_mix')->nullable();
            $table->decimal('mom_change_pct', 8, 2)->nullable();
            $table->timestamps();
            $table->unique(['area_id', 'sub_area_id', 'period']);
        });

        Schema::create('seo_engine_qa_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 255)->unique();
            $table->string('question', 512);
            $table->string('direct_answer', 500)->nullable();
            $table->longText('body_html')->nullable();
            $table->string('category', 64)->index();
            $table->json('related_rent_links')->nullable();
            $table->string('status', 16)->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('seo_engine_404_log', function (Blueprint $table) {
            $table->id();
            $table->string('path', 512)->index();
            $table->string('referrer', 1024)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('ip', 64)->nullable();
            $table->unsignedInteger('hit_count')->default(1);
            $table->timestamp('last_seen_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_engine_404_log');
        Schema::dropIfExists('seo_engine_qa_pages');
        Schema::dropIfExists('seo_engine_locality_stats');
        Schema::dropIfExists('seo_engine_redirects');
        Schema::dropIfExists('seo_engine_slug_history');
        Schema::dropIfExists('seo_engine_pages');
        Schema::dropIfExists('seo_engine_settings');
    }
};

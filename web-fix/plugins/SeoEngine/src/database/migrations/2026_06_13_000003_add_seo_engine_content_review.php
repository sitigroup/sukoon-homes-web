<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_engine_pages', function (Blueprint $table) {
            if (! Schema::hasColumn('seo_engine_pages', 'content_review_status')) {
                $table->string('content_review_status', 16)->nullable()->index()->after('content_generated_at');
            }
        });

        if (! Schema::hasTable('seo_engine_content_failures')) {
            Schema::create('seo_engine_content_failures', function (Blueprint $table) {
                $table->id();
                $table->string('path', 512)->index();
                $table->string('provider', 32)->nullable();
                $table->text('error')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_engine_content_failures');

        Schema::table('seo_engine_pages', function (Blueprint $table) {
            if (Schema::hasColumn('seo_engine_pages', 'content_review_status')) {
                $table->dropColumn('content_review_status');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('nearby_categories')) {
            return;
        }

        Schema::table('nearby_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('nearby_categories', 'cache_ttl_hours')) {
                $table->unsignedInteger('cache_ttl_hours')->nullable()->after('max_results_after_filter');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('nearby_categories')) {
            return;
        }

        Schema::table('nearby_categories', function (Blueprint $table) {
            if (Schema::hasColumn('nearby_categories', 'cache_ttl_hours')) {
                $table->dropColumn('cache_ttl_hours');
            }
        });
    }
};

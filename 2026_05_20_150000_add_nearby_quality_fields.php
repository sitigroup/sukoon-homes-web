<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nearby_categories')) {
            Schema::table('nearby_categories', function (Blueprint $table) {
                if (!Schema::hasColumn('nearby_categories', 'min_rating')) {
                    $table->decimal('min_rating', 2, 1)->default(3.5)->after('max_results');
                }
                if (!Schema::hasColumn('nearby_categories', 'min_reviews')) {
                    $table->unsignedSmallInteger('min_reviews')->default(3)->after('min_rating');
                }
                if (!Schema::hasColumn('nearby_categories', 'allow_unrated')) {
                    $table->boolean('allow_unrated')->default(false)->after('min_reviews');
                }
                if (!Schema::hasColumn('nearby_categories', 'hide_generic_places')) {
                    $table->boolean('hide_generic_places')->default(true)->after('allow_unrated');
                }
                if (!Schema::hasColumn('nearby_categories', 'hide_suspicious_same_location')) {
                    $table->boolean('hide_suspicious_same_location')->default(true)->after('hide_generic_places');
                }
                if (!Schema::hasColumn('nearby_categories', 'max_distance_m')) {
                    $table->unsignedInteger('max_distance_m')->nullable()->after('hide_suspicious_same_location');
                }
                if (!Schema::hasColumn('nearby_categories', 'max_results_after_filter')) {
                    $table->unsignedTinyInteger('max_results_after_filter')->nullable()->after('max_distance_m');
                }
            });

            $strictSlugs = ['restaurant', 'hospital', 'school', 'bank'];
            foreach ($strictSlugs as $slug) {
                DB::table('nearby_categories')
                    ->where('slug', $slug)
                    ->update([
                        'min_rating' => 3.5,
                        'min_reviews' => 3,
                        'allow_unrated' => false,
                        'hide_generic_places' => true,
                        'hide_suspicious_same_location' => true,
                    ]);
            }
        }

        if (Schema::hasTable('nearby_place_cache')) {
            Schema::table('nearby_place_cache', function (Blueprint $table) {
                if (!Schema::hasColumn('nearby_place_cache', 'user_ratings_total')) {
                    $table->unsignedInteger('user_ratings_total')->nullable()->after('rating');
                }
                if (!Schema::hasColumn('nearby_place_cache', 'business_status')) {
                    $table->string('business_status', 40)->nullable()->after('user_ratings_total');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nearby_categories')) {
            Schema::table('nearby_categories', function (Blueprint $table) {
                foreach ([
                    'min_rating',
                    'min_reviews',
                    'allow_unrated',
                    'hide_generic_places',
                    'hide_suspicious_same_location',
                    'max_distance_m',
                    'max_results_after_filter',
                ] as $column) {
                    if (Schema::hasColumn('nearby_categories', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('nearby_place_cache')) {
            Schema::table('nearby_place_cache', function (Blueprint $table) {
                foreach (['user_ratings_total', 'business_status'] as $column) {
                    if (Schema::hasColumn('nearby_place_cache', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

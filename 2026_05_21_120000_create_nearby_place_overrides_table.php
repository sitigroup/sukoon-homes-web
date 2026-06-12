<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('nearby_place_overrides')) {
            Schema::create('nearby_place_overrides', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('property_id')->nullable()->index();
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->string('google_place_id', 120)->nullable()->index();
                $table->string('normalized_name', 255)->nullable()->index();
                $table->string('action', 40);
                $table->string('display_name', 255)->nullable();
                $table->string('display_distance_text', 120)->nullable();
                $table->unsignedSmallInteger('display_order')->nullable();
                $table->boolean('is_recommended')->default(false);
                $table->decimal('manual_lat', 10, 7)->nullable();
                $table->decimal('manual_lng', 10, 7)->nullable();
                $table->text('manual_direction_url')->nullable();
                $table->boolean('disable_directions')->default(false);
                $table->text('admin_note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->index(['property_id', 'category_id', 'google_place_id'], 'nearby_override_scope_idx');
            });
        }

        if (Schema::hasTable('nearby_place_cache')) {
            Schema::table('nearby_place_cache', function (Blueprint $table) {
                if (!Schema::hasColumn('nearby_place_cache', 'quality_passed')) {
                    $table->boolean('quality_passed')->default(true)->after('place_types');
                }
                if (!Schema::hasColumn('nearby_place_cache', 'hidden_reason')) {
                    $table->string('hidden_reason', 80)->nullable()->after('quality_passed');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nearby_place_overrides')) {
            Schema::dropIfExists('nearby_place_overrides');
        }

        if (Schema::hasTable('nearby_place_cache')) {
            Schema::table('nearby_place_cache', function (Blueprint $table) {
                foreach (['quality_passed', 'hidden_reason'] as $column) {
                    if (Schema::hasColumn('nearby_place_cache', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

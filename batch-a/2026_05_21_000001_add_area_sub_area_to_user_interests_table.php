<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_interests', function (Blueprint $table) {
            if (! Schema::hasColumn('user_interests', 'state_id')) {
                $table->unsignedBigInteger('state_id')->nullable()->after('city');
            }
            if (! Schema::hasColumn('user_interests', 'city_id')) {
                $table->unsignedBigInteger('city_id')->nullable()->after('state_id');
            }
            if (! Schema::hasColumn('user_interests', 'area_id')) {
                $table->unsignedBigInteger('area_id')->nullable()->after('city_id');
            }
            if (! Schema::hasColumn('user_interests', 'sub_area_id')) {
                $table->unsignedBigInteger('sub_area_id')->nullable()->after('area_id');
            }
            if (! Schema::hasColumn('user_interests', 'area_name')) {
                $table->string('area_name', 255)->nullable()->after('sub_area_id');
            }
            if (! Schema::hasColumn('user_interests', 'sub_area_name')) {
                $table->string('sub_area_name', 255)->nullable()->after('area_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_interests', function (Blueprint $table) {
            foreach (['sub_area_name', 'area_name', 'sub_area_id', 'area_id', 'city_id', 'state_id'] as $column) {
                if (Schema::hasColumn('user_interests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_time_cache', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->string('google_place_id');
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('duration_text')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['property_id', 'google_place_id'], 'travel_time_cache_property_place_unique');
            $table->index(['property_id', 'expires_at']);
        });

        $settings = [
            ['nearby_places_travel_time_enabled', '0'],
            ['nearby_places_travel_cache_ttl_hours', '168'],
        ];

        foreach ($settings as [$type, $data]) {
            DB::table('settings')->updateOrInsert(['type' => $type], ['data' => $data]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_time_cache');

        DB::table('settings')->whereIn('type', [
            'nearby_places_travel_time_enabled',
            'nearby_places_travel_cache_ttl_hours',
        ])->delete();
    }
};

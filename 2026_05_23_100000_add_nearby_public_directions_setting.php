<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('settings')->where('type', 'nearby_places_public_directions_enabled')->exists()) {
            DB::table('settings')->insert([
                'type' => 'nearby_places_public_directions_enabled',
                'data' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('type', 'nearby_places_public_directions_enabled')->delete();
    }
};

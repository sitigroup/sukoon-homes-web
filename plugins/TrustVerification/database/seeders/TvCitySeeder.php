<?php

namespace App\Plugins\TrustVerification\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TvCitySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tv_cities')->insertOrIgnore([
            [
                'slug' => 'barmer',
                'label' => 'Barmer',
                'is_enabled' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

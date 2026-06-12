<?php

namespace App\Plugins\Theme\Database\Seeders;

use App\Plugins\Theme\Models\ThemeSetting;
use App\Plugins\Theme\Support\ThemePresets;
use Illuminate\Database\Seeder;

class ThemePresetSeeder extends Seeder
{
    /**
     * Creates draft-only defaults — does NOT publish (live site unchanged until admin publishes).
     */
    public function run(): void
    {
        $default = ThemePresets::defaultPreset();

        ThemeSetting::query()->firstOrCreate([], [
            'draft_payload' => $default,
            'published_payload' => null,
            'active_preset' => $default['slug'],
        ]);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('tv_settings')) {
            return;
        }

        $now = now();
        foreach ([
            ['order_number_prefix', 'TV'],
            ['order_number_format', 'random'],
            ['order_number_next_sequence', '1'],
            ['order_number_digits', '6'],
            ['order_number_separator', '-'],
        ] as [$key, $value]) {
            DB::table('tv_settings')->insertOrIgnore([
                'key' => $key,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('tv_settings')) {
            return;
        }

        DB::table('tv_settings')->whereIn('key', [
            'order_number_prefix',
            'order_number_format',
            'order_number_next_sequence',
            'order_number_digits',
            'order_number_separator',
        ])->delete();
    }
};

<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\AreaListing\Models\SubArea;
use Illuminate\Support\Facades\DB;

$fixed = 0;

foreach (['area_listing_property_locations', 'area_listing_project_locations'] as $table) {
    $rows = DB::table($table)->whereNotNull('sub_area_id')->where('sub_area_id', '>', 0)->get();
    foreach ($rows as $row) {
        if (empty($row->area_id)) {
            continue;
        }
        $sub = SubArea::find($row->sub_area_id);
        if (! $sub || (int) $sub->area_id !== (int) $row->area_id) {
            $area = \App\Plugins\AreaListing\Models\Area::find($row->area_id);
            $display = trim(($area->name ?? $row->area_name) . ', ' . ($row->city ?? '') . ', ' . ($row->state ?? ''), ', ');
            DB::table($table)->where('id', $row->id)->update([
                'sub_area_id' => null,
                'sub_area_name' => null,
                'detected_sub_area_name' => null,
                'display_address' => $display ?: $row->display_address,
                'updated_at' => now(),
            ]);
            $fixed++;
        }
    }
}

echo "repaired_orphan_sub_areas={$fixed}\n";

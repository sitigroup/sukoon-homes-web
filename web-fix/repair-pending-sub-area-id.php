<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\AreaListing\Services\AreaListingService;
use Illuminate\Support\Facades\DB;

$fixed = 0;
foreach (DB::table('area_listing_suggestions')->where('type', 'sub_area')->where('status', 'pending')->whereNull('area_id')->get() as $s) {
    $norm = $s->normalized_name;
    $loc = DB::table('area_listing_project_locations')
        ->whereNotNull('area_id')
        ->where(function ($q) use ($norm) {
            $q->whereRaw('LOWER(TRIM(detected_sub_area_name)) = ?', [$norm])
                ->orWhereRaw('LOWER(TRIM(sub_area_name)) = ?', [$norm]);
        })
        ->first();
    if (! $loc) {
        $loc = DB::table('area_listing_property_locations')
            ->whereNotNull('area_id')
            ->where(function ($q) use ($norm) {
                $q->whereRaw('LOWER(TRIM(detected_sub_area_name)) = ?', [$norm])
                    ->orWhereRaw('LOWER(TRIM(sub_area_name)) = ?', [$norm]);
            })
            ->first();
    }
    if ($loc && $loc->area_id) {
        DB::table('area_listing_suggestions')->where('id', $s->id)->update([
            'area_id' => $loc->area_id,
            'updated_at' => now(),
        ]);
        $fixed++;
        echo "suggestion {$s->id} -> area_id {$loc->area_id}\n";
    }
}

AreaListingService::syncUnlinkedListingLocations();
echo "backfilled area_id on $fixed pending sub suggestions\n";
echo 'pending subs: ' . DB::table('area_listing_suggestions')->where('status', 'pending')->where('type', 'sub_area')->count() . "\n";

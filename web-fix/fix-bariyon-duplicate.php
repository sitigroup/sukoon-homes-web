<?php

/**
 * Merge duplicate sub-area "Bariyon Ka Was" (id 59) into canonical "Bariyon Ka Vas" (id 2).
 * Usage: php fix-bariyon-duplicate.php [--dry-run]
 */

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$dryRun = in_array('--dry-run', $argv ?? [], true);

$canonicalSubId = 2;
$duplicateSubId = 59;
$canonicalAreaId = 2;

$canonicalSub = DB::table('area_listing_sub_areas')->where('id', $canonicalSubId)->first();
$duplicateSub = DB::table('area_listing_sub_areas')->where('id', $duplicateSubId)->first();
$canonicalArea = DB::table('area_listing_areas')->where('id', $canonicalAreaId)->first();

if (! $canonicalSub || ! $duplicateSub || ! $canonicalArea) {
    echo "Missing canonical or duplicate records.\n";
    exit(1);
}

echo ($dryRun ? '[DRY RUN] ' : '') . "Merge sub {$duplicateSubId} ({$duplicateSub->name}) -> {$canonicalSubId} ({$canonicalSub->name})\n";
echo "Canonical area: {$canonicalArea->name} (id {$canonicalAreaId})\n\n";

$updates = [
    'property' => DB::table('area_listing_property_locations')->where('sub_area_id', $duplicateSubId)->count(),
    'project' => DB::table('area_listing_project_locations')->where('sub_area_id', $duplicateSubId)->count(),
];

echo "Listings to re-link: properties={$updates['property']} projects={$updates['project']}\n";

if ($dryRun) {
  echo "No changes made.\n";
  exit(0);
}

DB::transaction(function () use ($canonicalSubId, $duplicateSubId, $canonicalAreaId, $canonicalSub, $canonicalArea) {
    $locationPatch = [
        'area_id' => $canonicalAreaId,
        'sub_area_id' => $canonicalSubId,
        'area_name' => $canonicalArea->name,
        'sub_area_name' => $canonicalSub->name,
        'detected_area_name' => $canonicalArea->name,
        'detected_sub_area_name' => $canonicalSub->name,
        'updated_at' => now(),
    ];

    DB::table('area_listing_property_locations')
        ->where('sub_area_id', $duplicateSubId)
        ->update($locationPatch);

    DB::table('area_listing_project_locations')
        ->where('sub_area_id', $duplicateSubId)
        ->update($locationPatch);

    DB::table('area_listing_suggestions')
        ->where('type', 'sub_area')
        ->where(function ($q) use ($duplicateSubId) {
            $q->where('id', 42)
                ->orWhereRaw('LOWER(TRIM(normalized_name)) IN (?, ?)', ['bariyon ka was', 'bariyon ka vas']);
        })
        ->where('status', 'pending')
        ->update([
            'status' => 'rejected',
            'review_note' => 'Merged duplicate sub-area into canonical id ' . $canonicalSubId,
            'updated_at' => now(),
        ]);

    DB::table('area_listing_sub_areas')
        ->where('id', $duplicateSubId)
        ->update([
            'workflow_status' => 'archived',
            'status' => 0,
            'archived_at' => now(),
            'updated_at' => now(),
        ]);
});

echo "Done. Sub-area {$duplicateSubId} archived; listings now point to {$canonicalSubId} under {$canonicalArea->name}.\n";

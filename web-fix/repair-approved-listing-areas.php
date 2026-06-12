<?php
/**
 * One-time: materialize area/sub-area for already-approved listings still showing pending suggestions.
 * Run: php repair-approved-listing-areas.php
 */
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\AreaListing\Services\AreaListingService;
use Illuminate\Support\Facades\DB;

$fixed = 0;

$projectIds = DB::table('area_listing_project_locations as l')
    ->join('projects as p', 'p.id', '=', 'l.project_id')
    ->where('p.request_status', 'approved')
    ->where(function ($q) {
        $q->where(function ($a) {
            $a->whereNull('l.area_id')
                ->where(function ($n) {
                    $n->whereNotNull('l.detected_area_name')->where('l.detected_area_name', '!=', '')
                        ->orWhereNotNull('l.area_name')->where('l.area_name', '!=', '');
                });
        })->orWhere(function ($s) {
            $s->whereNull('l.sub_area_id')
                ->where(function ($n) {
                    $n->whereNotNull('l.detected_sub_area_name')->where('l.detected_sub_area_name', '!=', '')
                        ->orWhereNotNull('l.sub_area_name')->where('l.sub_area_name', '!=', '');
                });
        });
    })
    ->pluck('l.project_id');

foreach ($projectIds as $id) {
    AreaListingService::materializeLocationOnListingApproval((int) $id, 'project');
    echo "project $id\n";
    $fixed++;
}

$propertyIds = DB::table('area_listing_property_locations as l')
    ->join('propertys as p', 'p.id', '=', 'l.property_id')
    ->where('p.request_status', 'approved')
    ->where(function ($q) {
        $q->where(function ($a) {
            $a->whereNull('l.area_id')
                ->where(function ($n) {
                    $n->whereNotNull('l.detected_area_name')->where('l.detected_area_name', '!=', '')
                        ->orWhereNotNull('l.area_name')->where('l.area_name', '!=', '');
                });
        })->orWhere(function ($s) {
            $s->whereNull('l.sub_area_id')
                ->where(function ($n) {
                    $n->whereNotNull('l.detected_sub_area_name')->where('l.detected_sub_area_name', '!=', '')
                        ->orWhereNotNull('l.sub_area_name')->where('l.sub_area_name', '!=', '');
                });
        });
    })
    ->pluck('l.property_id');

foreach ($propertyIds as $id) {
    AreaListingService::materializeLocationOnListingApproval((int) $id, 'property');
    echo "property $id\n";
    $fixed++;
}

echo "done, processed $fixed listings\n";

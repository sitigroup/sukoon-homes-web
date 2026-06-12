<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\AreaListing\Services\AreaListingService;
use Illuminate\Support\Facades\DB;

// Create Temple sub-area under Mangi (59) if missing
$s56 = DB::table('area_listing_suggestions')->where('id', 56)->first();
if ($s56 && ! AreaListingService::masterRecordExistsForSuggestion($s56)) {
    $s56->area_id = 59;
    AreaListingService::createMasterFromSuggestionRecord($s56);
    AreaListingService::propagateApprovedSuggestion($s56);
    echo "created Temple sub-area under Mangi\n";
}

$synced = AreaListingService::syncUnlinkedListingLocations();
echo "synced listings: $synced\n";

$loc = DB::table('area_listing_project_locations')->where('project_id', 7)->first();
echo "project 7: area_id={$loc->area_id} sub_area_id={$loc->sub_area_id}\n";

$subs = DB::table('area_listing_sub_areas')->where('area_id', 59)->count();
echo "Mangi sub_areas count: $subs\n";

$projs = DB::table('area_listing_project_locations')->where('area_id', 59)->count();
echo "Mangi project listings: $projs\n";

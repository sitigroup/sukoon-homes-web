<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\AreaListing\Services\AreaListingService;
use Illuminate\Support\Facades\DB;

foreach (DB::table('projects')->where('request_status', 'approved')->pluck('id') as $id) {
    AreaListingService::materializeLocationOnListingApproval((int) $id, 'project');
    echo "project $id\n";
}

foreach (DB::table('propertys')->where('request_status', 'approved')->pluck('id') as $id) {
    AreaListingService::materializeLocationOnListingApproval((int) $id, 'property');
    echo "property $id\n";
}

echo 'pending subs: ' . DB::table('area_listing_suggestions')->where('status', 'pending')->where('type', 'sub_area')->count() . "\n";

$loc = DB::table('area_listing_project_locations')->where('project_id', 7)->first();
echo 'project7: ' . json_encode($loc) . "\n";

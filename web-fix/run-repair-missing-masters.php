<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\AreaListing\Services\AreaListingService;
use Illuminate\Support\Facades\DB;

$created = AreaListingService::repairSuggestionsApprovedWithoutMaster();
echo "created master records from wrongly closed suggestions: $created\n";

echo 'mangi areas: ' . DB::table('area_listing_areas')->where('normalized_name', 'mangi')->count() . "\n";
echo 'temple subs: ' . DB::table('area_listing_sub_areas')->where('normalized_name', 'temple')->count() . "\n";

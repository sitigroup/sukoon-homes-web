<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\AreaListing\Services\AreaListingService;
use Illuminate\Support\Facades\DB;

$closed = AreaListingService::resolveStalePendingSuggestions();
echo "resolved stale pending: $closed\n";

AreaListingService::materializeLocationOnListingApproval(7, 'project');
echo "materialized project 7\n";

$pending = DB::table('area_listing_suggestions')
    ->where('status', 'pending')
    ->whereIn('id', [53, 54])
    ->pluck('name', 'id');
echo "pending 53/54: " . json_encode($pending) . "\n";

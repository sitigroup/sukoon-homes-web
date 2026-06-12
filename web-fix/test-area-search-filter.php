<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Plugins\AreaListing\Services\AreaListingService;
use Illuminate\Http\Request;

$p19 = Property::find(19);
echo 'Property 19 status: ' . ($p19->status ?? 'n/a') . ' type: ' . ($p19->propery_type ?? '') . PHP_EOL;

$loc = DB::table('area_listing_property_locations')->where('property_id', 19)->first();
echo 'Location row: area_id=' . ($loc->area_id ?? 'null') . ' detected_area=' . ($loc->detected_area_name ?? '') . PHP_EOL;

// Direct subquery test
$ids = DB::table('area_listing_property_locations')
    ->where('city_id', 1)
    ->where(function ($q) {
        $q->where('area_id', 45)
            ->orWhere(function ($p) {
                $p->whereNull('area_id')
                    ->where(function ($n) {
                        $n->where('area_name', 'Krishna Nagar')
                            ->orWhere('detected_area_name', 'Krishna Nagar');
                    });
            });
    })
    ->pluck('property_id')
    ->toArray();
echo 'Direct DB match property_ids: ' . implode(',', $ids) . PHP_EOL;

$filters = ['location' => ['city_id' => 1, 'area_id' => 45]];
$request = Request::create('/', 'GET', []);
$query = Property::query();
$filtered = AreaListingService::applyPropertyFilters($query, $request, $filters);
echo 'All properties via filter: ' . $filtered->pluck('id')->implode(',') . PHP_EOL;

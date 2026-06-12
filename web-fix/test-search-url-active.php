<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Plugins\AreaListing\Services\AreaListingService;
use Illuminate\Http\Request;

$encoded = 'eyJwcm9wZXJ0eV90eXBlIjoiIiwiY2F0ZWdvcnlfaWQiOiIiLCJjYXRlZ29yeV9zbHVnX2lkIjoiIiwicGFyYW1ldGVycyI6W10sIm5lYXJieV9wbGFjZXMiOltdLCJsb2NhdGlvbiI6eyJjb3VudHJ5IjoiSW5kaWEiLCJzdGF0ZSI6IlJhamFzdGhhbiIsImNpdHkiOiJCYXJtZXIiLCJwbGFjZV9pZCI6IiIsInN0YXRlX2lkIjoiIiwiY2l0eV9pZCI6IiIsImFyZWFfaWQiOjQ1LCJzdWJfYXJlYV9pZCI6IiJ9LCJwcmljZSI6eyJtaW5fcHJpY2UiOjAsIm1heF9wcmljZSI6MH0sInBvc3RlZF9zaW5jZSI6MCwic2VhcmNoIjoiIiwiZmxhZ3MiOnsicHJvbW90ZWQiOjAsImdldF9hbGxfcHJlbWl1bV9wcm9wZXJ0aWVzIjowLCJtb3N0X3ZpZXdzIjowLCJtb3N0X2xpa2VkIjowfX0=';
$filters = json_decode(base64_decode($encoded), true);
$request = Request::create('/', 'GET', []);

$q = Property::whereIn('propery_type', [0, 1])->where(function ($query) {
    $query->onlyActive();
});
$q = AreaListingService::applyPropertyFilters($q, $request, $filters);
$loc = $filters['location'];
if (! empty($loc['city'])) {
    $q->where('city', 'like', '%' . $loc['city'] . '%');
}
if (! empty($loc['state'])) {
    $q->where('state', 'like', '%' . $loc['state'] . '%');
}
echo 'User URL + onlyActive: ' . $q->pluck('id')->implode(',') . PHP_EOL;

$q2 = Property::whereIn('propery_type', [0, 1])->where(function ($query) {
    $query->onlyActive();
});
$q2 = AreaListingService::applyPropertyFilters($q2, $request, ['location' => ['city_id' => 1, 'area_id' => 45]]);
echo 'city_id=1 area 45 + onlyActive: ' . $q2->pluck('id')->implode(',') . PHP_EOL;

$approvedArea = Property::whereIn('propery_type', [0, 1])
    ->where(function ($query) {
        $query->onlyActive();
    })
    ->whereIn('id', function ($s) {
        $s->select('property_id')->from('area_listing_property_locations')->where('area_id', 45);
    });
echo 'Any approved with area_id 45: ' . $approvedArea->pluck('id')->implode(',') . PHP_EOL;

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

$p = Property::find(19);
echo 'Property 19: status=' . $p->status . ' request_status=' . ($p->request_status ?? 'null') . ' city=' . $p->city . ' propery_type=' . $p->propery_type . PHP_EOL;

$onlyActive = Property::where('id', 19)->where(function ($q) { $q->onlyActive(); })->exists();
echo 'Passes onlyActive: ' . ($onlyActive ? 'yes' : 'no') . PHP_EOL;

$query = Property::query();
$query = AreaListingService::applyPropertyFilters($query, $request, $filters);
$location = $filters['location'];
$query->where('city', 'like', '%' . $location['city'] . '%');
$query->where('state', 'like', '%' . $location['state'] . '%');
echo 'No onlyActive + area + city text: ' . $query->pluck('id')->implode(',') . PHP_EOL;

// Check if deploy has our filter fix
$ref = new ReflectionMethod(AreaListingService::class, 'applyListingFilters');
echo 'applyListingFilters params: ' . $ref->getNumberOfParameters() . PHP_EOL;

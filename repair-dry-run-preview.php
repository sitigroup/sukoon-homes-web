<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use Illuminate\Support\Facades\DB;

$propertyTable = (new Property())->getTable();
$missing = Property::query()->whereNotExists(function ($q) use ($propertyTable) {
    $q->select(DB::raw(1))
        ->from('area_listing_property_locations')
        ->whereColumn('area_listing_property_locations.property_id', $propertyTable . '.id');
})->count();

echo 'scanned_total=' . Property::count() . "\n";
echo 'missing_count=' . $missing . "\n";
echo 'rows_to_create=' . $missing . "\n";

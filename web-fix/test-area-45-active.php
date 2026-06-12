<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$approved = DB::table('area_listing_property_locations as l')
    ->join('propertys as p', 'p.id', '=', 'l.property_id')
    ->where(function ($q) {
        $q->where('l.area_id', 45)
            ->orWhere(function ($p) {
                $p->whereNull('l.area_id')->where('l.detected_area_name', 'Krishna Nagar');
            });
    })
    ->select('p.id', 'p.status', 'p.request_status', 'p.city')
    ->get();

echo "Properties in Krishna Nagar (all statuses):\n";
foreach ($approved as $row) {
    echo "  id={$row->id} status={$row->status} request_status={$row->request_status} city={$row->city}\n";
}

$cityId = DB::table('area_listing_cities')->where('name', 'Barmer')->value('id');
echo "Barmer city_id=$cityId\n";

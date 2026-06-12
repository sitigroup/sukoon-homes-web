<?php
$path = '/www/wwwroot/admin-homes/app/Http/Controllers/Api/PropertyApiController.php';
$text = file_get_contents($path);
$insert = <<<'PHP'

            if (! empty($user_interest->area_id) || ! empty($user_interest->sub_area_id)) {
                if (class_exists(\App\Plugins\AreaListing\Services\AreaListingService::class)) {
                    $areaRequest = Request::create('/', 'GET', array_filter([
                        'area_id' => $user_interest->area_id,
                        'sub_area_id' => $user_interest->sub_area_id,
                        'city_id' => $user_interest->city_id,
                    ]));
                    $property = \App\Plugins\AreaListing\Services\AreaListingService::applyPropertyFilters($property, $areaRequest);
                }
            } elseif ($user_interest->city != '') {
                $city = $user_interest->city;
                $property = $property->where('city', $city);
            }
PHP;

if (strpos($text, 'applyPropertyFilters($property, $areaRequest)') !== false) {
    echo "already patched\n";
    exit(0);
}

$needle = "            }\n\n\n            if (\$user_interest->property_type != '') {";
if (strpos($text, $needle) === false) {
    echo "insert point not found\n";
    exit(1);
}

file_put_contents($path, str_replace($needle, "            }" . $insert . "\n            if (\$user_interest->property_type != '') {", $text));
echo "patched ok\n";

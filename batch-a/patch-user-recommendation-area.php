<?php
/**
 * Run on server: php patch-user-recommendation-area.php
 * Patches get_user_recommendation city filter to support area_id / sub_area_id.
 */

$path = '/www/wwwroot/admin-homes/app/Http/Controllers/Api/PropertyApiController.php';
$content = file_get_contents($path);

$old = <<<'OLD'
            if ($user_interest->city != '') {
                $city = $user_interest->city;
                $property = $property->where('city', $city);
            }
OLD;

$new = <<<'NEW'
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
NEW;

if (strpos($content, $old) === false) {
    echo "pattern not found - may already be patched\n";
    exit(strpos($content, 'applyPropertyFilters($property, $areaRequest)') !== false ? 0 : 1);
}

file_put_contents($path, str_replace($old, $new, $content, $count));
echo "replaced={$count}\n";

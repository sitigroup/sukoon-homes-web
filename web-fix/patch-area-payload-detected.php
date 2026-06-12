<?php

$path = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Services/AreaListingService.php';
$content = file_get_contents($path);

$old = <<<'PHP'
            'area_name' => $area?->name ?? $location?->area_name ?? $location?->detected_area_name,
            'sub_area_id' => $subArea?->id ?? $location?->sub_area_id,
            'sub_area_name' => $subArea?->name ?? $location?->sub_area_name ?? $location?->detected_sub_area_name,
            'city' => $location?->city ?? ($area?->city_name ?: $area?->city) ?? $city,
PHP;

$new = <<<'PHP'
            'area_name' => $area?->name ?? $location?->area_name ?? $location?->detected_area_name,
            'sub_area_id' => $subArea?->id ?? $location?->sub_area_id,
            'sub_area_name' => $subArea?->name ?? $location?->sub_area_name ?? $location?->detected_sub_area_name,
            'detected_area_name' => $location?->detected_area_name ?? ($area ? null : ($location?->area_name ?? null)),
            'detected_sub_area_name' => $location?->detected_sub_area_name ?? ($subArea ? null : ($location?->sub_area_name ?? null)),
            'location_source' => $location?->location_source ?? $location?->source ?? 'manual',
            'is_verified' => (bool) ($location?->is_verified ?? false),
            'city' => $location?->city ?? ($area?->city_name ?: $area?->city) ?? $city,
PHP;

if (strpos($content, "'detected_area_name' => \$location?->detected_area_name") !== false) {
    echo "areaPayload already patched\n";
    exit(0);
}

if (strpos($content, $old) === false) {
    echo "areaPayload block not found\n";
    exit(1);
}

file_put_contents($path, str_replace($old, $new, $content));
echo "areaPayload patched\n";

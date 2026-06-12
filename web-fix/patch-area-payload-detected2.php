<?php
$path = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Services/AreaListingService.php';
$t = file_get_contents($path);
if (strpos($t, "'detected_area_name' => \$location?->detected_area_name") !== false) {
    echo "already patched\n";
    exit(0);
}
$needle = "'sub_area_name' => \$subArea?->name ?? \$location?->sub_area_name ?? \$location?->detected_sub_area_name,\n            'city' =>";
$insert = "'sub_area_name' => \$subArea?->name ?? \$location?->sub_area_name ?? \$location?->detected_sub_area_name,\n            'detected_area_name' => \$location?->detected_area_name ?? (\$area ? null : (\$location?->area_name ?? null)),\n            'detected_sub_area_name' => \$location?->detected_sub_area_name ?? (\$subArea ? null : (\$location?->sub_area_name ?? null)),\n            'location_source' => \$location?->location_source ?? \$location?->source ?? 'manual',\n            'is_verified' => (bool) (\$location?->is_verified ?? false),\n            'city' =>";
if (strpos($t, $needle) === false) {
    echo "needle not found\n";
    exit(1);
}
file_put_contents($path, str_replace($needle, $insert, $t));
echo "patched\n";

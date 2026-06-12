<?php

$path = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Services/AreaListingService.php';
$content = file_get_contents($path);

if (strpos($content, 'area_listing_user_portal') !== false) {
    echo "already patched\n";
    exit(0);
}

$needle = "        if (request()->boolean('area_listing_admin_save')) {\n            return true;\n        }\n\n        return self::userCanAutoCreateAreas(self::resolveAreaListingActor());";

$replace = "        if (request()->boolean('area_listing_admin_save')) {\n            return true;\n        }\n\n        // User dashboard (/user/...) — suggest only, never auto-create in master area tables\n        if (request()->boolean('area_listing_user_portal')) {\n            return false;\n        }\n\n        return self::userCanAutoCreateAreas(self::resolveAreaListingActor());";

if (strpos($content, $needle) === false) {
    echo "needle not found\n";
    exit(1);
}

file_put_contents($path, str_replace($needle, $replace, $content));
echo "backend patched\n";

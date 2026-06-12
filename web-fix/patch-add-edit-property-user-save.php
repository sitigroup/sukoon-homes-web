<?php
$files = [
    '/www/wwwroot/homes.sukoon.group/src/components/agent/property/AddProperty.jsx',
    '/www/wwwroot/homes.sukoon.group/src/components/agent/property/EditProperty.jsx',
];

$importNeedle = "import { canManageAreaListingInContext } from '@/plugins/area-listing/areaListingPermissions';";
$importReplace = "import { canManageAreaListingInContext, buildAreaListingSaveFields } from '@/plugins/area-listing/areaListingPermissions';";

$blockNeedle = <<<'JS'
                state_id: selectedLocationAddress.state_id,
                city_id: selectedLocationAddress.city_id,
                area_id: selectedLocationAddress.area_id,
                sub_area_id: selectedLocationAddress.sub_area_id,
                area_name: selectedLocationAddress.area_name,
                sub_area_name: selectedLocationAddress.sub_area_name,
                detected_area_name: selectedLocationAddress.detected_area_name,
                detected_sub_area_name: selectedLocationAddress.detected_sub_area_name,
                area_listing_source: selectedLocationAddress.area_listing_source || "google",
                location_is_verified: selectedLocationAddress.location_is_verified || false,
JS;

$blockReplace = <<<'JS'
                ...buildAreaListingSaveFields(selectedLocationAddress, {
                    isUserPortal: Boolean(router?.asPath?.includes('/user/')),
                }),
JS;

foreach ($files as $path) {
    $c = file_get_contents($path);
    if (! str_contains($c, 'buildAreaListingSaveFields')) {
        if (str_contains($c, $importNeedle)) {
            $c = str_replace($importNeedle, $importReplace, $c);
        } elseif (str_contains($c, "from '@/plugins/area-listing/areaListingPermissions'")) {
            $c = preg_replace(
                "/from '@\/plugins\/area-listing\/areaListingPermissions'/",
                "from '@/plugins/area-listing/areaListingPermissions'\nimport { buildAreaListingSaveFields } from '@/plugins/area-listing/areaListingPermissions'",
                $c,
                1
            );
        }
        if (str_contains($c, $blockNeedle)) {
            $c = str_replace($blockNeedle, $blockReplace, $c);
            echo "OK: $path area save fields\n";
        } else {
            echo "WARN: $path block not found\n";
        }
    } else {
        echo "SKIP: $path already patched\n";
    }

    $c = preg_replace(
        '/\.\.\.\(isUserRoute \? \{ area_listing_user_portal: 1 \} : \{\}\),\s*\n\s*\.\.\.\(router\.asPath\?\.includes\(\'\/user\/\'\) \? \{ area_listing_user_portal: 1 \} : \{\}\),/',
        '',
        $c
    );
    $c = str_replace(
        "...(isUserRoute ? { area_listing_user_portal: 1 } : {}),\n",
        '',
        $c
    );
    $c = str_replace(
        "...(router.asPath?.includes('/user/') ? { area_listing_user_portal: 1 } : {}),\n",
        '',
        $c
    );

    file_put_contents($path, $c);
}

echo "DONE\n";

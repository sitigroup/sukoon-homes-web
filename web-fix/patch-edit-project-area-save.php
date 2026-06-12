<?php
$path = '/www/wwwroot/homes.sukoon.group/src/components/agent/project/EditProject.jsx';
$c = file_get_contents($path);

if (! str_contains($c, 'buildAreaListingSaveFields')) {
    $c = str_replace(
        "import { setPaymentReturnUrl } from '@/utils/paymentReturn'",
        "import { setPaymentReturnUrl } from '@/utils/paymentReturn'\nimport { buildAreaListingSaveFields } from '@/plugins/area-listing/areaListingPermissions'",
        $c
    );
    echo "OK: import\n";
}

$old = <<<'JS'
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
                location: selectedLocationAddress.formattedAddress,
                manual_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",
                customer_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",
                client_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",
                ...(router.asPath?.includes('/user/') ? { area_listing_user_portal: 1 } : {}),
JS;

$new = <<<'JS'
                ...buildAreaListingSaveFields(selectedLocationAddress, {
                    isUserPortal: router.asPath?.includes('/user/'),
                }),
                area_listing_source: selectedLocationAddress.area_listing_source || "google",
                location_is_verified: selectedLocationAddress.location_is_verified || false,
                location: selectedLocationAddress.formattedAddress,
                manual_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",
                customer_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",
                client_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",
JS;

if (str_contains($c, $old)) {
    $c = str_replace($old, $new, $c);
    echo "OK: save payload\n";
} elseif (str_contains($c, 'buildAreaListingSaveFields')) {
    echo "SKIP: already patched\n";
} else {
    echo "WARN: block not found\n";
    exit(1);
}

file_put_contents($path, $c);
echo "DONE EditProject.jsx\n";

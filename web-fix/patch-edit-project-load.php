<?php
$path = '/www/wwwroot/homes.sukoon.group/src/components/agent/project/EditProject.jsx';
$edit = file_get_contents($path);

$old = <<<'JS'
                // Set location data
                setSelectedLocationAddress({
                    city: projectData.city || "",
                    state: projectData.state || "",
                    country: projectData.country || "",
                    formattedAddress: projectData.location || "",
                    manualAddress: projectData.area_listing?.manual_address || "",
                    clientAddress: projectData.area_listing?.manual_address || "",
                    latitude: projectData.latitude || 0,
                    longitude: projectData.longitude || 0,
                    state_id: projectData.area_listing?.state_id || "",
                    city_id: projectData.area_listing?.city_id || "",
                    area_id: projectData.area_listing?.area_id || "",
                    sub_area_id: projectData.area_listing?.sub_area_id || "",
                    area_name: projectData.area_listing?.area_name || "",
                    sub_area_name: projectData.area_listing?.sub_area_name || "",
                    detected_area_name: projectData.area_listing?.area_name || "",
                    detected_sub_area_name: projectData.area_listing?.sub_area_name || ""
                });
JS;

$new = <<<'JS'
                // Set location data
                const areaListing = projectData.area_listing || {};
                setSelectedLocationAddress({
                    city: projectData.city || areaListing.city || "",
                    state: projectData.state || areaListing.state || "",
                    country: projectData.country || areaListing.country || "",
                    formattedAddress: projectData.location || areaListing.full_address || "",
                    manualAddress: areaListing.manual_address || "",
                    clientAddress: areaListing.manual_address || "",
                    latitude: projectData.latitude || areaListing.latitude || 0,
                    longitude: projectData.longitude || areaListing.longitude || 0,
                    lat: projectData.latitude || areaListing.latitude || 0,
                    lng: projectData.longitude || areaListing.longitude || 0,
                    state_id: areaListing.state_id || "",
                    city_id: areaListing.city_id || "",
                    area_id: areaListing.area_id || "",
                    sub_area_id: areaListing.sub_area_id || "",
                    area_name: areaListing.area_name || "",
                    sub_area_name: areaListing.sub_area_name || "",
                    detected_area_name: areaListing.detected_area_name || (areaListing.area_id ? "" : (areaListing.area_name || "")),
                    detected_sub_area_name: areaListing.detected_sub_area_name || (areaListing.sub_area_id ? "" : (areaListing.sub_area_name || "")),
                    area_listing_source: areaListing.location_source || areaListing.source || "manual",
                    location_is_verified: !!areaListing.is_verified,
                });
JS;

if (str_contains($edit, 'const areaListing = projectData.area_listing')) {
    echo "SKIP: already patched\n";
    exit(0);
}

if (! str_contains($edit, $old)) {
    echo "FAIL: block not found\n";
    exit(1);
}

file_put_contents($path, str_replace($old, $new, $edit));
echo "OK: EditProject load patched\n";

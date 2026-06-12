<?php
/**
 * Align project create/edit with property area-listing (user portal suggest-only, detected fields).
 */

$addPath = '/www/wwwroot/homes.sukoon.group/src/components/agent/project/AddProject.jsx';
$editPath = '/www/wwwroot/homes.sukoon.group/src/components/agent/project/EditProject.jsx';

// --- AddProject: user portal flag on create ---
$add = file_get_contents($addPath);
$addNeedle = "                location_is_verified: selectedLocationAddress.location_is_verified || false,\n\n                // Floor Details";
$addReplace = "                location_is_verified: selectedLocationAddress.location_is_verified || false,\n                client_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || \"\",\n                ...(isUserRoute ? { area_listing_user_portal: 1 } : {}),\n\n                // Floor Details";
if (! str_contains($add, 'area_listing_user_portal')) {
    if (str_contains($add, $addNeedle)) {
        $add = str_replace($addNeedle, $addReplace, $add);
        file_put_contents($addPath, $add);
        echo "OK: AddProject area_listing_user_portal\n";
    } else {
        echo "WARN: AddProject needle not found\n";
    }
} else {
    echo "SKIP: AddProject already has area_listing_user_portal\n";
}

// --- EditProject: load location like EditProperty ---
$edit = file_get_contents($editPath);
$oldLoad = <<<'JS'
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

$newLoad = <<<'JS'
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

if (str_contains($edit, 'areaListing.detected_area_name')) {
    echo "SKIP: EditProject load already patched\n";
} elseif (str_contains($edit, $oldLoad)) {
    $edit = str_replace($oldLoad, $newLoad, $edit);
    echo "OK: EditProject load location\n";
} else {
    echo "WARN: EditProject load block not found\n";
}

// --- EditProject: save payload ---
$editSaveNeedle = "                customer_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || \"\",\n                plans: plans,";
$editSaveReplace = "                customer_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || \"\",\n                client_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || \"\",\n                ...(router.asPath?.includes('/user/') ? { area_listing_user_portal: 1 } : {}),\n                plans: plans,";

if (! str_contains($edit, 'area_listing_user_portal')) {
    if (str_contains($edit, $editSaveNeedle)) {
        $edit = str_replace($editSaveNeedle, $editSaveReplace, $edit);
        file_put_contents($editPath, $edit);
        echo "OK: EditProject save area_listing_user_portal\n";
    } else {
        file_put_contents($editPath, $edit);
        echo "WARN: EditProject save needle not found\n";
    }
} else {
    file_put_contents($editPath, $edit);
    echo "SKIP: EditProject save already has area_listing_user_portal\n";
}

// --- EditProject: initial state fields (optional, for consistency) ---
$editInitOld = "    const [selectedLocationAddress, setSelectedLocationAddress] = useState({\n        city: '',\n        state: '',\n        country: '',\n        formattedAddress: '',\n        manualAddress: '',\n        clientAddress: '',\n        latitude: 0,\n        longitude: 0\n    });";
$editInitNew = "    const [selectedLocationAddress, setSelectedLocationAddress] = useState({\n        city: '',\n        state: '',\n        country: '',\n        formattedAddress: '',\n        manualAddress: '',\n        clientAddress: '',\n        latitude: 0,\n        longitude: 0,\n        lat: 0,\n        lng: 0,\n        state_id: '',\n        city_id: '',\n        area_id: '',\n        sub_area_id: '',\n        area_name: '',\n        sub_area_name: '',\n        detected_area_name: '',\n        detected_sub_area_name: '',\n        area_listing_source: 'google',\n        location_is_verified: false,\n    });";
if (str_contains($edit, "detected_area_name: ''")) {
    echo "SKIP: EditProject initial state already extended\n";
} elseif (str_contains($edit, $editInitOld)) {
    $edit = str_replace($editInitOld, $editInitNew, $edit);
    file_put_contents($editPath, $edit);
    echo "OK: EditProject initial state\n";
} else {
    file_put_contents($editPath, $edit);
}

echo "DONE\n";

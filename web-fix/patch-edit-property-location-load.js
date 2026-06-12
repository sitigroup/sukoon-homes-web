const fs = require('fs');
const path = '/www/wwwroot/homes.sukoon.group/src/components/agent/property/EditProperty.jsx';
let content = fs.readFileSync(path, 'utf8');

const oldBlock = `                // Set location data
                setSelectedLocationAddress({
                    city: propertyData.city || "",
                    state: propertyData.state || "",
                    country: propertyData.country || "",
                    formattedAddress: propertyData.address || "",
                    manualAddress: propertyData.client_address || propertyData.area_listing?.manual_address || "",
                    clientAddress: propertyData.client_address || propertyData.area_listing?.manual_address || "",
                    latitude: propertyData.latitude || 0,
                    longitude: propertyData.longitude || 0,
                    state_id: propertyData.area_listing?.state_id || "",
                    city_id: propertyData.area_listing?.city_id || "",
                    area_id: propertyData.area_listing?.area_id || "",
                    sub_area_id: propertyData.area_listing?.sub_area_id || "",
                    area_name: propertyData.area_listing?.area_name || "",
                    sub_area_name: propertyData.area_listing?.sub_area_name || "",
                    detected_area_name: propertyData.area_listing?.area_name || "",
                    detected_sub_area_name: propertyData.area_listing?.sub_area_name || ""
                });`;

const newBlock = `                // Set location data
                const areaListing = propertyData.area_listing || {};
                setSelectedLocationAddress({
                    city: propertyData.city || areaListing.city || "",
                    state: propertyData.state || areaListing.state || "",
                    country: propertyData.country || areaListing.country || "",
                    formattedAddress: propertyData.address || areaListing.full_address || "",
                    manualAddress: propertyData.client_address || areaListing.manual_address || "",
                    clientAddress: propertyData.client_address || areaListing.manual_address || "",
                    latitude: propertyData.latitude || areaListing.latitude || 0,
                    longitude: propertyData.longitude || areaListing.longitude || 0,
                    lat: propertyData.latitude || areaListing.latitude || 0,
                    lng: propertyData.longitude || areaListing.longitude || 0,
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
                });`;

if (!content.includes(oldBlock)) {
    console.error('EditProperty location load block not found');
    process.exit(1);
}

content = content.replace(oldBlock, newBlock);
fs.writeFileSync(path, content);
console.log('OK: EditProperty location load patched');

const fs = require('fs');
const path = '/www/wwwroot/homes.sukoon.group/src/components/agent/property/EditProperty.jsx';
let content = fs.readFileSync(path, 'utf8');

const marker = 'setSelectedLocationAddress({';
const idx = content.indexOf('const areaListing = propertyData.area_listing');
if (idx === -1) {
  const legacy = `detected_sub_area_name: areaListing.detected_sub_area_name || (areaListing.sub_area_id ? "" : (areaListing.sub_area_name || "")),
                    area_listing_source:`;
  if (!content.includes(legacy)) {
    console.error('EditProperty area listing load block not found');
    process.exit(1);
  }
  const withConfirmed = `detected_sub_area_name: areaListing.detected_sub_area_name || (areaListing.sub_area_id ? "" : (areaListing.sub_area_name || "")),
                    area_listing_user_confirmed: !!(areaListing.area_id || areaListing.sub_area_id),
                    area_listing_source:`;
  content = content.replace(legacy, withConfirmed);
} else {
  console.log('EditProperty already has areaListing load block');
}

if (!content.includes('area_listing_user_confirmed')) {
  console.error('Failed to add area_listing_user_confirmed');
  process.exit(1);
}

fs.writeFileSync(path, content);
console.log('OK: EditProperty unified load');

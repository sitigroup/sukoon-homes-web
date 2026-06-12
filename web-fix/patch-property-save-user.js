const fs = require('fs');

const files = [
  '/www/wwwroot/homes.sukoon.group/src/components/agent/property/AddProperty.jsx',
  '/www/wwwroot/homes.sukoon.group/src/components/agent/property/EditProperty.jsx',
];

const importLine = "import LocationComponent from '@/components/reusable-components/add-property/LocationComponent';";
const importWith = "import LocationComponent from '@/components/reusable-components/add-property/LocationComponent';\nimport { buildAreaListingSaveFields } from '@/plugins/area-listing/areaListingPermissions';";

const oldBlock = `                state_id: selectedLocationAddress.state_id,
                city_id: selectedLocationAddress.city_id,
                area_id: selectedLocationAddress.area_id,
                sub_area_id: selectedLocationAddress.sub_area_id,
                area_name: selectedLocationAddress.area_name,
                sub_area_name: selectedLocationAddress.sub_area_name,
                detected_area_name: selectedLocationAddress.detected_area_name,
                detected_sub_area_name: selectedLocationAddress.detected_sub_area_name,
                area_listing_source: selectedLocationAddress.area_listing_source || "google",
                location_is_verified: selectedLocationAddress.location_is_verified || false,`;

const newBlock = `                ...buildAreaListingSaveFields(selectedLocationAddress, {
                    isUserPortal: Boolean(router?.asPath?.includes('/user/')),
                }),`;

for (const path of files) {
  let c = fs.readFileSync(path, 'utf8');
  if (!c.includes('buildAreaListingSaveFields')) {
    if (!c.includes(importWith)) {
      c = c.replace(importLine, importWith);
    }
    if (!c.includes(oldBlock)) {
      console.log('FAIL block:', path);
      continue;
    }
    c = c.replace(oldBlock, newBlock);
    c = c.replace(/\.\.\.\(isUserRoute \? \{ area_listing_user_portal: 1 \} : \{\}\),\n/g, '');
    c = c.replace(/\.\.\.\(router\.asPath\?\.includes\('\/user\/'\) \? \{ area_listing_user_portal: 1 \} : \{\}\),\n/g, '');
    fs.writeFileSync(path, c);
    console.log('OK:', path);
  } else {
    console.log('SKIP:', path);
  }
}

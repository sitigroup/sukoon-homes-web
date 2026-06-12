const fs = require('fs');
const path = '/www/wwwroot/homes.sukoon.group/src/components/agent/property/AddProperty.jsx';
let c = fs.readFileSync(path, 'utf8');

if (c.includes('area_listing_user_portal')) {
  console.log('already patched');
  process.exit(0);
}

const needle = `                client_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",
                price: propertyFormData.propertyPrice,`;

const replace = `                client_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",
                ...(isUserRoute ? { area_listing_user_portal: 1 } : {}),
                price: propertyFormData.propertyPrice,`;

if (!c.includes(needle)) {
  console.error('needle not found');
  process.exit(1);
}

fs.writeFileSync(path, c.replace(needle, replace));
console.log('OK');

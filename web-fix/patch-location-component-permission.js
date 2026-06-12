const fs = require('fs');
const path = '/www/wwwroot/homes.sukoon.group/src/components/reusable-components/add-property/LocationComponent.jsx';
let c = fs.readFileSync(path, 'utf8');

if (!c.includes('useSelector')) {
  c = c.replace(
    "import { useRef, useState } from 'react'",
    "import { useRef, useState } from 'react'\nimport { useSelector } from 'react-redux'"
  );
}

if (!c.includes('canManageAreaListing')) {
  c = c.replace(
    '    const t = useTranslation();',
    "    const t = useTranslation();\n    const canManageAreaListing = useSelector((state) => !!state.User?.data?.can_manage_area_listing);"
  );

  c = c.replace(
    '<AreaSubAreaSelector\n                        compactSeparate\n                        requiresCity\n                        selectedLocationAddress={selectedLocationAddress}\n                        setSelectedLocationAddress={setSelectedLocationAddress}\n                        showStateCity={false}\n                    />',
    '<AreaSubAreaSelector\n                        compactSeparate\n                        requiresCity\n                        canManageAreaListing={canManageAreaListing}\n                        selectedLocationAddress={selectedLocationAddress}\n                        setSelectedLocationAddress={setSelectedLocationAddress}\n                        showStateCity={false}\n                    />'
  );
}

fs.writeFileSync(path, c);
console.log('OK: LocationComponent patched');

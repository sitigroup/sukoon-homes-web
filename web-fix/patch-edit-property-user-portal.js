const fs = require('fs');
const path = '/www/wwwroot/homes.sukoon.group/src/components/agent/property/EditProperty.jsx';
let c = fs.readFileSync(path, 'utf8');

if (!c.includes('useRouter')) {
  if (!c.includes("from 'react'")) {
    console.error('react import not found');
    process.exit(1);
  }
  c = c.replace(
    "import { useEffect, useState } from 'react';",
    "import { useEffect, useState } from 'react';\nimport { useRouter } from 'next/router';"
  );
}

if (!c.includes('const router = useRouter()')) {
  const marker = 'const EditProperty = ({ params = [] }) => {';
  if (!c.includes(marker)) {
    console.error('EditProperty marker not found');
    process.exit(1);
  }
  c = c.replace(marker, `${marker}\n    const router = useRouter();`);
}

if (!c.includes('area_listing_user_portal')) {
  c = c.replace(
    '                client_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",\n                parameters: parameters,',
    `                client_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",\n                ...(router.asPath?.includes('/user/') ? { area_listing_user_portal: 1 } : {}),\n                parameters: parameters,`
  );
}

fs.writeFileSync(path, c);
console.log('OK: EditProperty user portal flag');

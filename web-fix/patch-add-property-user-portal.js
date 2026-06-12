const fs = require('fs');
const path = '/www/wwwroot/homes.sukoon.group/src/components/agent/property/AddProperty.jsx';
let c = fs.readFileSync(path, 'utf8');

if (!c.includes('useRouter')) {
  c = c.replace(
    "import { useEffect, useState } from 'react';",
    "import { useEffect, useState } from 'react';\nimport { useRouter } from 'next/router';"
  );
}

if (!c.includes('const router = useRouter()')) {
  c = c.replace(
    'const AddProperty = () => {',
    'const AddProperty = () => {\n    const router = useRouter();'
  );
}

if (!c.includes('area_listing_user_portal')) {
  const patterns = [
    ['                client_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",\n                parameters:', '                client_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",\n                ...(router.asPath?.includes(\'/user/\') ? { area_listing_user_portal: 1 } : {}),\n                parameters:'],
    ['                client_address: selectedLocationAddress.clientAddress || "",\n                parameters:', '                client_address: selectedLocationAddress.clientAddress || "",\n                ...(router.asPath?.includes(\'/user/\') ? { area_listing_user_portal: 1 } : {}),\n                parameters:'],
  ];
  let done = false;
  for (const [from, to] of patterns) {
    if (c.includes(from)) {
      c = c.replace(from, to);
      done = true;
      break;
    }
  }
  if (!done) {
    console.error('AddProperty save block not found');
    process.exit(1);
  }
}

fs.writeFileSync(path, c);
console.log('OK: AddProperty user portal flag');

const fs = require('fs');
const path = '/www/wwwroot/homes.sukoon.group/src/components/agent/property/AddProperty.jsx';
let content = fs.readFileSync(path, 'utf8');

const pattern = /const handleLocationSelect = \(address\) => \{[\s\S]*?\};/;
const replacement = `const handleLocationSelect = (address) => {
        setSelectedLocationAddress(prev => ({
            ...prev,
            ...address,
            formattedAddress: address.formattedAddress || prev.formattedAddress,
            latitude: address.latitude ?? address.lat ?? prev.latitude,
            longitude: address.longitude ?? address.lng ?? prev.longitude,
            lat: address.lat ?? address.latitude ?? prev.lat,
            lng: address.lng ?? address.longitude ?? prev.lng,
            clientAddress: address.clientAddress ?? prev.clientAddress,
            manualAddress: address.manualAddress ?? address.clientAddress ?? prev.manualAddress,
        }));
    };`;

if (!pattern.test(content)) {
    console.error('AddProperty handleLocationSelect not found');
    process.exit(1);
}

content = content.replace(pattern, replacement);
fs.writeFileSync(path, content);
console.log('OK: AddProperty location select patched');

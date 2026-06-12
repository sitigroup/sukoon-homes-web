const fs = require('fs');
const path = '/www/wwwroot/homes.sukoon.group/src/components/agent/property/EditProperty.jsx';
let content = fs.readFileSync(path, 'utf8');

const oldBlock = `    const handleEditLocationSelect = (address) => {
        // Update the form field with the selected address from the Map component
        setSelectedLocationAddress(prev => ({
            ...prev,
            city: address.city || prev.city,
            state: address.state || prev.state,
            country: address.country || prev.country,
            formattedAddress: address.formattedAddress || prev.formattedAddress,
            latitude: address.latitude || address.lat || prev.latitude,
            longitude: address.longitude || address.lng || prev.longitude
        }));
    };`;

const newBlock = `    const handleEditLocationSelect = (address) => {
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

if (!content.includes(oldBlock)) {
    console.error('EditProperty handleEditLocationSelect block not found');
    process.exit(1);
}

content = content.replace(oldBlock, newBlock);
fs.writeFileSync(path, content);
console.log('OK: EditProperty location select patched');

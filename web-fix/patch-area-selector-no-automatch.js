const fs = require('fs');
const path = '/www/wwwroot/homes.sukoon.group/src/plugins/area-listing/AreaSubAreaSelector.jsx';
let content = fs.readFileSync(path, 'utf8');

const areaGuard = `  useEffect(() => {
    if (!canManageAreaListing) return undefined;
    if (selectedLocationAddress?.area_id || !areas.length) return;`;

const subGuard = `  useEffect(() => {
    if (!canManageAreaListing) return undefined;
    if (!selectedArea || selectedLocationAddress?.sub_area_id) return;`;

if (content.includes('if (!canManageAreaListing) return undefined;\n    if (selectedLocationAddress?.area_id')) {
  console.log('already patched');
  process.exit(0);
}

const areaNeedle = `  useEffect(() => {
    if (selectedLocationAddress?.area_id || !areas.length) return;`;
const subNeedle = `  useEffect(() => {
    if (!selectedArea || selectedLocationAddress?.sub_area_id) return;`;

if (!content.includes(areaNeedle) || !content.includes(subNeedle)) {
  console.error('needles not found');
  process.exit(1);
}

content = content.replace(areaNeedle, areaGuard);
content = content.replace(subNeedle, subGuard);
fs.writeFileSync(path, content);
console.log('OK: AreaSubAreaSelector auto-match gated by canManageAreaListing');

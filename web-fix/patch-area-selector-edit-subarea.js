const fs = require('fs');
const path = '/www/wwwroot/homes.sukoon.group/src/plugins/area-listing/areaListingApi.js';
let api = fs.readFileSync(path, 'utf8');
if (!api.includes('getAreaListingSubAreas')) {
  api = api.replace(
    'export const getAreaListingAreas = async',
    `export const getAreaListingSubAreas = async ({ area_id = "" } = {}) => {
  const res = await api.get("area-listing/sub-areas", { params: { area_id } });
  return res.data;
};

export const getAreaListingAreas = async`
  );
  fs.writeFileSync(path, api);
  console.log('OK: areaListingApi getAreaListingSubAreas');
} else {
  console.log('SKIP: getAreaListingSubAreas exists');
}

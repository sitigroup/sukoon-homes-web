const fs = require('fs');
const path = '/www/wwwroot/homes.sukoon.group/src/api/apiRoutes.js';
let c = fs.readFileSync(path, 'utf8');

const fnStart = 'export const updatePostPropertyApi = async ({';
const fnIdx = c.indexOf(fnStart);
if (fnIdx < 0) {
  console.log('FAIL: updatePostPropertyApi not found');
  process.exit(1);
}

const slice = c.slice(fnIdx, fnIdx + 2500);
if (slice.includes('area_listing_user_portal =')) {
  console.log('SKIP: updatePostPropertyApi already has param');
  process.exit(0);
}

c = c.replace(
  '  area_listing_source = "google",\n}) => {\n  let data = new FormData();\n\n  // Append the property data',
  `  area_listing_source = "google",
  state_id = "",
  city_id = "",
  location_is_verified = "",
  manual_address = "",
  customer_address = "",
  area_listing_user_portal = "",
}) => {
  let data = new FormData();

  // Append the property data`
);

c = c.replace(
  '  if (area_listing_source) data.append("area_listing_source", area_listing_source);\n  if (price) {\n    data.append("price", price);\n  }\n  if (category_id) {\n    data.append("category_id", category_id);\n  }\n  if (property_type) {\n    data.append("property_type", property_type);\n  }\n  if (video_link) {',
  `  if (area_listing_source) data.append("area_listing_source", area_listing_source);
  if (state_id) data.append("state_id", state_id);
  if (city_id) data.append("city_id", city_id);
  if (location_is_verified !== "" && location_is_verified !== undefined && location_is_verified !== null) {
    data.append("location_is_verified", location_is_verified ? "1" : "0");
  }
  if (manual_address) data.append("manual_address", manual_address);
  if (customer_address) data.append("customer_address", customer_address);
  if (area_listing_user_portal) data.append("area_listing_user_portal", area_listing_user_portal);
  if (price) {
    data.append("price", price);
  }
  if (category_id) {
    data.append("category_id", category_id);
  }
  if (property_type) {
    data.append("property_type", property_type);
  }
  if (video_link) {`
);

fs.writeFileSync(path, c);
console.log('OK: updatePostPropertyApi patched');

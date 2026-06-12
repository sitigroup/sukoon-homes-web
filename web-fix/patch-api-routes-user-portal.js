const fs = require('fs');
const path = '/www/wwwroot/homes.sukoon.group/src/api/apiRoutes.js';
let c = fs.readFileSync(path, 'utf8');

const extraParams = `  state_id = "",
  city_id = "",
  location_is_verified = "",
  manual_address = "",
  customer_address = "",
  area_listing_user_portal = "",
`;

const extraAppendPost = `
  if (state_id) formData.append("state_id", state_id);
  if (city_id) formData.append("city_id", city_id);
  if (location_is_verified !== "" && location_is_verified !== undefined && location_is_verified !== null) {
    formData.append("location_is_verified", location_is_verified ? "1" : "0");
  }
  if (manual_address) formData.append("manual_address", manual_address);
  if (customer_address) formData.append("customer_address", customer_address);
  if (area_listing_user_portal) formData.append("area_listing_user_portal", area_listing_user_portal);
`;

const extraAppendUpdate = `
  if (state_id) data.append("state_id", state_id);
  if (city_id) data.append("city_id", city_id);
  if (location_is_verified !== "" && location_is_verified !== undefined && location_is_verified !== null) {
    data.append("location_is_verified", location_is_verified ? "1" : "0");
  }
  if (manual_address) data.append("manual_address", manual_address);
  if (customer_address) data.append("customer_address", customer_address);
  if (area_listing_user_portal) data.append("area_listing_user_portal", area_listing_user_portal);
`;

function patchFunction(name, areaListingSourceLine, formVar) {
  if (c.includes(`${name}`) && c.includes('area_listing_user_portal =')) {
    console.log(`SKIP: ${name} already has area_listing_user_portal`);
    return;
  }
  const needle = `${areaListingSourceLine}\n}) => {`;
  if (!c.includes(needle)) {
    console.log(`WARN: ${name} needle not found`);
    return;
  }
  c = c.replace(needle, `${areaListingSourceLine}\n${extraParams}}) => {`);
  const appendNeedle =
    formVar === 'formData'
      ? '  if (area_listing_source) formData.append("area_listing_source", area_listing_source);'
      : '  if (area_listing_source) data.append("area_listing_source", area_listing_source);';
  const appendReplace =
    formVar === 'formData'
      ? `  if (area_listing_source) formData.append("area_listing_source", area_listing_source);${extraAppendPost}`
      : `  if (area_listing_source) data.append("area_listing_source", area_listing_source);${extraAppendUpdate}`;
  if (!c.includes(appendReplace.trim().split('\n')[0] + '\n  if (area_listing_user_portal)')) {
    c = c.replace(appendNeedle, appendReplace);
    console.log(`OK: ${name}`);
  }
}

patchFunction('postPropertyApi', '  area_listing_source = "google",', 'formData');
patchFunction('updatePostPropertyApi', '  area_listing_source = "google",', 'data');

// postProjectApi — area appends exist but params missing from destructuring
const projectNeedle = `  remove_video = 0,
}) => {
  let data = new FormData();`;
const projectReplace = `  remove_video = 0,
  area_id = "",
  sub_area_id = "",
  area_name = "",
  sub_area_name = "",
  detected_area_name = "",
  detected_sub_area_name = "",
  area_listing_source = "google",
${extraParams}}) => {
  let data = new FormData();`;

if (!c.includes('postProjectApi') || c.includes('postProjectApi') && c.match(/postProjectApi[\s\S]*?area_listing_user_portal/)) {
  console.log('SKIP or CHECK: postProjectApi');
} else if (c.includes(projectNeedle)) {
  c = c.replace(projectNeedle, projectReplace);
  c = c.replace(
    '  if (area_listing_source) data.append("area_listing_source", area_listing_source);\n  if (video_link) {',
    `  if (area_listing_source) data.append("area_listing_source", area_listing_source);${extraAppendUpdate}\n  if (video_link) {`
  );
  console.log('OK: postProjectApi');
} else {
  console.log('WARN: postProjectApi needle not found');
}

fs.writeFileSync(path, c);
console.log('DONE apiRoutes.js');

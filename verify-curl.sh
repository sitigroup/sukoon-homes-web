#!/bin/bash
curl -s -o /dev/null -w "homes=%{http_code}\n" "https://homes.sukoon.group/properties/?lang=en"
curl -s -o /dev/null -w "admin=%{http_code}\n" "https://admin-homes.sukoon.group/"
curl -s -o /dev/null -w "area-listing=%{http_code}\n" "https://admin-homes.sukoon.group/area-listing"
echo "--- public api ---"
for p in \
  "/api/area-listing/states" \
  "/api/area-listing/cities?state_id=1" \
  "/api/area-listing/areas?city_id=1" \
  "/api/location/areas?city_id=1" \
  "/api/area-listing/sub-areas?area_id=2" \
  "/api/area-listing/areas?per_page=5&page=1&city_id=1" \
  "/api/area-listing/areas?per_page=5"
do
  code=$(curl -s -o /tmp/api_out.json -w "%{http_code}" "https://admin-homes.sukoon.group${p}")
  echo "${p} => ${code}"
done
grep -E 'manual_address|full_address|"latitude"|"longitude"' /tmp/api_out.json && echo "LEAK_FOUND" || echo "no_leak_in_last"

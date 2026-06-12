#!/bin/bash
TOKEN="$1"
BASE="https://admin-homes.sukoon.group"

test_ep() {
  local name="$1"
  local method="$2"
  local url="$3"
  local data="$4"
  local code
  code=$(curl -s -o /tmp/body.json -w "%{http_code}" -X "$method" "$url" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    ${data:+-d "$data"})
  echo "$name => HTTP $code"
  head -c 200 /tmp/body.json 2>/dev/null; echo
}

test_ep "permissions" GET "$BASE/api/area-listing/permissions"
test_ep "suggest-area" POST "$BASE/api/location/suggest-area" '{"city_id":1,"name":"Test Area Postman"}'
test_ep "repair-dry-run" POST "$BASE/area-listing/repair-locations/dry-run" '{}'
test_ep "drift-check" POST "$BASE/area-listing/drift-check" '{}'

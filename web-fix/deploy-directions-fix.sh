#!/bin/bash
set -e
HOST=srv1534644
BASE=/www/wwwroot/homes.sukoon.group
FIX="$(dirname "$0")"

scp -o ConnectTimeout=20 "$FIX/openGoogleMapsDirections.js" "$HOST:$BASE/src/utils/openGoogleMapsDirections.js"
scp -o ConnectTimeout=20 "$FIX/PropertyAddress.jsx" "$HOST:$BASE/src/components/property-detail/PropertyAddress.jsx"
scp -o ConnectTimeout=20 "$FIX/UserPropertyDetails.jsx" "$HOST:$BASE/src/components/property-detail/UserPropertyDetails.jsx"
scp -o ConnectTimeout=20 "$FIX/UserProjectDetails.jsx" "$HOST:$BASE/src/components/project-details/UserProjectDetails.jsx"
scp -o ConnectTimeout=20 "$FIX/PropertyDetails.jsx" "$HOST:$BASE/src/components/property-detail/PropertyDetails.jsx" 2>/dev/null || scp -o ConnectTimeout=20 "$FIX/../batch-a/PropertyDetails.jsx" "$HOST:$BASE/src/components/property-detail/PropertyDetails.jsx"

for f in AgentPropertyDetails.jsx ProjectDetails.jsx AgentProjectDetails.jsx; do
  src="$FIX/$f"
  [ -f "$src" ] || src="$FIX/../batch-a/$f"
  if [ -f "$src" ]; then
    scp -o ConnectTimeout=20 "$src" "$HOST:$BASE/src/components/property-detail/$f" 2>/dev/null || \
    scp -o ConnectTimeout=20 "$src" "$HOST:$BASE/src/components/project-details/$f" 2>/dev/null || true
  fi
done

ssh -o ConnectTimeout=20 "$HOST" "php -r \"
\\\$f='$BASE/src/utils/en.json';
\\\$d=json_decode(file_get_contents(\\\$f),true);
\\\$d['getDirections']='Get Directions';
file_put_contents(\\\$f,json_encode(\\\$d,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL);
echo 'en ok\n';
cd $BASE && npm run build 2>&1 | tail -6 && pm2 restart homes-sukoon 2>&1 | tail -2
\""

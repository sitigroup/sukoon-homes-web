#!/usr/bin/env bash
# TASK A4 — Property JSON-LD + SSR property detail
set -euo pipefail
HOST="${DEPLOY_HOST:-srv1534644-ipv4}"
WEB="${HOMES_ROOT:-/www/wwwroot/homes.sukoon.group}"
PLUGIN="$WEB/src/plugins/property-detail-switcher"
FIX="$(cd "$(dirname "$0")" && pwd)"

echo "==> Backup (see *.backup-a4 on server)"
ssh -o ConnectTimeout=25 "$HOST" "
  cp -a '$WEB/pages/property-details/[slug]/index.jsx' '$WEB/pages/property-details/[slug]/index.jsx.backup-a4'
  cp -a '$WEB/src/components/pagescomponents/PropertyDetailPage.jsx' '$WEB/src/components/pagescomponents/PropertyDetailPage.jsx.backup-a4'
  cp -a '$PLUGIN/PropertyDetailsSwitcher.jsx' '$PLUGIN/PropertyDetailsSwitcher.jsx.backup-a4'
  cp -a '$PLUGIN/CustomPropertyDetailsPage.jsx' '$PLUGIN/CustomPropertyDetailsPage.jsx.backup-a4'
  cp -a '$WEB/src/components/property-detail/PropertyDetails.jsx' '$WEB/src/components/property-detail/PropertyDetails.jsx.backup-a4'
"

echo "==> Copy files + build (no restart — run pm2 restart manually after verify)"
scp -o ConnectTimeout=25 "$FIX/jsonld.js" "$HOST:$WEB/src/utils/jsonld.js"
scp -o ConnectTimeout=25 "$FIX/property-details-index.jsx" "$HOST:$WEB/pages/property-details/[slug]/index.jsx"
scp -o ConnectTimeout=25 "$FIX/PropertyDetailPage.jsx" "$HOST:$WEB/src/components/pagescomponents/PropertyDetailPage.jsx"
scp -o ConnectTimeout=25 "$FIX/PropertyDetailsSwitcher.jsx" "$HOST:$PLUGIN/PropertyDetailsSwitcher.jsx"
scp -o ConnectTimeout=25 "$FIX/CustomPropertyDetailsPage.jsx" "$HOST:$PLUGIN/CustomPropertyDetailsPage.jsx"
scp -o ConnectTimeout=25 "$FIX/OfficialPropertyDetails.jsx" "$HOST:$WEB/src/components/property-detail/PropertyDetails.jsx"
scp -o ConnectTimeout=25 "$FIX/search-location-page.jsx" "$HOST:$WEB/pages/search/[citySlug]/index.jsx"
scp -o ConnectTimeout=25 "$FIX/search-location-page.jsx" "$HOST:$WEB/pages/search/[citySlug]/[areaSlug]/index.jsx"
scp -o ConnectTimeout=25 "$FIX/search-location-page.jsx" "$HOST:$WEB/pages/search/[citySlug]/[areaSlug]/[subAreaSlug]/index.jsx"

ssh -o ConnectTimeout=25 "$HOST" "cd '$WEB' && npm run build"

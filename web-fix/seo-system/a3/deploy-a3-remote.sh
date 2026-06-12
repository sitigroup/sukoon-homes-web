#!/usr/bin/env bash
# TASK A3 — Area Wise SEO + Organization schema
set -euo pipefail
HOST="${DEPLOY_HOST:-srv1534644-ipv4}"
ADMIN="${ADMIN_ROOT:-/www/wwwroot/admin-homes}"
WEB="${HOMES_ROOT:-/www/wwwroot/homes.sukoon.group}"
FIX="$(cd "$(dirname "$0")" && pwd)"
PLUGIN="$ADMIN/app/Plugins/AreaListing"

echo "==> Backup A3 originals"
ssh -o ConnectTimeout=25 "$HOST" "
  cp -a '$PLUGIN/views/admin/index.blade.php' '$PLUGIN/views/admin/index.blade.php.backup-a3' 2>/dev/null || true
  cp -a '$PLUGIN/views/admin/partials/area-actions.blade.php' '$PLUGIN/views/admin/partials/area-actions.blade.php.backup-a3' 2>/dev/null || true
  cp -a '$PLUGIN/views/admin/partials/sub-area-actions.blade.php' '$PLUGIN/views/admin/partials/sub-area-actions.blade.php.backup-a3' 2>/dev/null || true
  cp -a '$PLUGIN/Http/Controllers/Admin/AreaListingAdminController.php' '$PLUGIN/Http/Controllers/Admin/AreaListingAdminController.php.backup-a3' 2>/dev/null || true
  cp -a '$PLUGIN/Http/Controllers/Api/AreaListingApiController.php' '$PLUGIN/Http/Controllers/Api/AreaListingApiController.php.backup-a3' 2>/dev/null || true
  cp -a '$WEB/src/utils/helperFunction.js' '$WEB/src/utils/helperFunction.js.backup-a3' 2>/dev/null || true
"

echo "==> Copy Laravel plugin files"
scp -o ConnectTimeout=25 "$FIX/index.blade.php" "$HOST:$PLUGIN/views/admin/index.blade.php"
scp -o ConnectTimeout=25 "$FIX/area-actions.blade.php" "$HOST:$PLUGIN/views/admin/partials/area-actions.blade.php"
scp -o ConnectTimeout=25 "$FIX/sub-area-actions.blade.php" "$HOST:$PLUGIN/views/admin/partials/sub-area-actions.blade.php"
scp -o ConnectTimeout=25 "$FIX/AreaListingAdminController.php" "$HOST:$PLUGIN/Http/Controllers/Admin/AreaListingAdminController.php"
scp -o ConnectTimeout=25 "$FIX/AreaListingApiController.php" "$HOST:$PLUGIN/Http/Controllers/Api/AreaListingApiController.php"

echo "==> Copy Next.js files"
scp -o ConnectTimeout=25 "$FIX/locationSeoMeta.js" "$HOST:$WEB/src/utils/locationSeoMeta.js"
scp -o ConnectTimeout=25 "$FIX/helperFunction.js" "$HOST:$WEB/src/utils/helperFunction.js"
scp -o ConnectTimeout=25 "$FIX/search-location-page.jsx" "$HOST:$WEB/pages/search/[citySlug]/index.jsx"
scp -o ConnectTimeout=25 "$FIX/search-location-page.jsx" "$HOST:$WEB/pages/search/[citySlug]/[areaSlug]/index.jsx"
scp -o ConnectTimeout=25 "$FIX/search-location-page.jsx" "$HOST:$WEB/pages/search/[citySlug]/[areaSlug]/[subAreaSlug]/index.jsx"

echo "==> Laravel optimize + Next build"
ssh -o ConnectTimeout=25 "$HOST" "
  chown -R www:www '$PLUGIN' '$WEB/src/utils/locationSeoMeta.js' '$WEB/src/utils/helperFunction.js' '$WEB/pages/search' 2>/dev/null || true
  cd '$ADMIN' && sudo -u www php artisan optimize:clear
  cd '$WEB' && npm run build 2>&1 | tail -12
  pm2 restart homes-sukoon 2>/dev/null || true
"

echo "DEPLOY_A3_OK"

#!/bin/bash
set -e
ADMIN=/www/wwwroot/admin-homes
HOMES=/www/wwwroot/homes.sukoon.group
FIX=/tmp/web-fix-deploy

mkdir -p "$FIX"

echo "=== Deploy backend ==="
cp "$FIX/AreaListingService.php" "$ADMIN/app/Plugins/AreaListing/Services/AreaListingService.php"
php -l "$ADMIN/app/Plugins/AreaListing/Services/AreaListingService.php"

echo "=== Deploy frontend ==="
cp "$FIX/AddProject.jsx" "$HOMES/src/components/agent/project/AddProject.jsx"
php "$FIX/patch-edit-project-area-save.php" 2>/dev/null || true

for f in Search.jsx PropertySideFilter.jsx MainSwiper.jsx AreaSubAreaSelector.jsx LocationComponent.jsx areaListingPermissions.js areaListingUtils.js SearchBox.jsx locationSearchUrl.js; do
  if [ -f "$FIX/$f" ]; then
    case "$f" in
      Search.jsx) cp "$FIX/$f" "$HOMES/src/components/search/Search.jsx" ;;
      PropertySideFilter.jsx) cp "$FIX/$f" "$HOMES/src/components/pagescomponents/PropertySideFilter.jsx" ;;
      MainSwiper.jsx) cp "$FIX/$f" "$HOMES/src/components/mainswiper/MainSwiper.jsx" ;;
      AreaSubAreaSelector.jsx) cp "$FIX/$f" "$HOMES/src/plugins/area-listing/AreaSubAreaSelector.jsx" ;;
      LocationComponent.jsx) cp "$FIX/$f" "$HOMES/src/components/reusable-components/add-property/LocationComponent.jsx" ;;
      areaListingPermissions.js) cp "$FIX/$f" "$HOMES/src/plugins/area-listing/areaListingPermissions.js" ;;
      areaListingUtils.js) cp "$FIX/$f" "$HOMES/src/plugins/area-listing/areaListingUtils.js" ;;
      SearchBox.jsx) cp "$FIX/$f" "$HOMES/src/components/mainswiper/SearchBox.jsx" ;;
      locationSearchUrl.js) cp "$FIX/$f" "$HOMES/src/utils/locationSearchUrl.js" ;;
    esac
  fi
done

echo "=== Laravel cache clear ==="
cd "$ADMIN"
php artisan optimize:clear 2>/dev/null || true
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear 2>/dev/null || true

echo "=== OPcache (if available) ==="
php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo 'opcache_reset OK\n'; } else { echo 'no opcache\n'; }"

echo "=== Next.js rebuild ==="
cd "$HOMES"
rm -rf .next/cache 2>/dev/null || true
npm run build

echo "=== PM2 restart ==="
pm2 restart homes-sukoon

echo "=== DONE ==="

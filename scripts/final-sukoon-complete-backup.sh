#!/bin/bash
# Sukoon Homes — final complete backup (plugins + minimal core patches + web + DB)
# Run on server: bash /path/to/final-sukoon-complete-backup.sh
set -eu

ADMIN_ROOT="${ADMIN_ROOT:-/www/wwwroot/admin-homes}"
HOMES_ROOT="${HOMES_ROOT:-/www/wwwroot/homes.sukoon.group}"
STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_NAME="sukoon-final-complete-${STAMP}"
BACKUP_DIR="${ADMIN_ROOT}/storage/backups/${BACKUP_NAME}"
ARCHIVE="${ADMIN_ROOT}/storage/backups/${BACKUP_NAME}.tar.gz"

mkdir -p "${BACKUP_DIR}"/{admin/plugins,admin/patches,admin/public-js,admin/views,web/plugins,web/patches,web/pages,db,meta}

copy_if_exists() {
  local src="$1"
  local dest="$2"
  if [[ -f "$src" ]]; then
    mkdir -p "$(dirname "$dest")"
    cp -a "$src" "$dest"
  fi
}

echo "==> Plugins (full)"
cp -a "${ADMIN_ROOT}/app/Plugins/AreaListing" "${BACKUP_DIR}/admin/plugins/"
cp -a "${ADMIN_ROOT}/app/Plugins/NearbyPlaces" "${BACKUP_DIR}/admin/plugins/"
if [[ -d "${ADMIN_ROOT}/app/Plugins/TrustVerification" ]]; then
  cp -a "${ADMIN_ROOT}/app/Plugins/TrustVerification" "${BACKUP_DIR}/admin/plugins/"
fi
if [[ -d "${ADMIN_ROOT}/app/Plugins/Theme" ]]; then
  cp -a "${ADMIN_ROOT}/app/Plugins/Theme" "${BACKUP_DIR}/admin/plugins/"
fi
cp -a "${HOMES_ROOT}/src/plugins" "${BACKUP_DIR}/web/plugins/"

echo "==> Admin core patches (minimal — re-apply after wrteam updates)"
ADMIN_PATCHES=(
  "app/Http/Controllers/Api/CategoryApiController.php"
  "app/Http/Controllers/Api/AppointmentApiController.php"
  "app/Http/Controllers/Api/ProfileApiController.php"
  "app/Http/Controllers/ProjectController.php"
  "app/Http/Controllers/CustomersController.php"
  "app/Http/Controllers/PropertController.php"
)
for rel in "${ADMIN_PATCHES[@]}"; do
  copy_if_exists "${ADMIN_ROOT}/${rel}" "${BACKUP_DIR}/admin/patches/${rel}"
done

copy_if_exists "${ADMIN_ROOT}/public/assets/js/maps-helper.js" \
  "${BACKUP_DIR}/admin/public-js/maps-helper.js"
copy_if_exists "${ADMIN_ROOT}/public/css/property-table-scroll.css" \
  "${BACKUP_DIR}/admin/public-js/property-table-scroll.css"

for blade in create.blade.php edit.blade.php index.blade.php; do
  copy_if_exists "${ADMIN_ROOT}/resources/views/property/${blade}" \
    "${BACKUP_DIR}/admin/views/property/${blade}"
done

echo "==> Web core patches (minimal)"
WEB_PATCHES=(
  "src/pages/_app.js"
  "src/components/layout/Layout.jsx"
  "src/components/layout/Header.jsx"
  "src/components/layout/MobileMenu.jsx"
  "src/components/pages/Home.jsx"
  "src/components/listings/AllListings.jsx"
  "src/components/mainswiper/SearchBox.jsx"
  "src/components/pagescomponents/PropertySideFilter.jsx"
  "src/components/pagescomponents/ProjectSideFilter.jsx"
  "src/components/google-maps/GoogleMap.jsx"
  "src/components/google-maps/AdvancedMapMarker.jsx"
  "src/components/google-maps/PropertyOnMapView.jsx"
  "src/components/google-maps/CustomLocationAutocomplete.jsx"
  "src/components/google-maps/LocationSearchWithRadius.jsx"
  "src/components/property-detail/LocationInsightsGroup.jsx"
  "src/components/property-detail/PropertyInfoBanner.jsx"
  "src/components/user/EditProperty.jsx"
  "src/components/user/UserRoot.jsx"
  "src/components/user/UserPersonalizedFeeds.jsx"
  "src/components/reusable-components/add-property/LocationComponent.jsx"
  "src/components/agent/appointment/modals/ViewAllSchedules.jsx"
  "src/components/agent/appointment/modals/AddExtraTimeSlotModal.jsx"
  "src/components/appointment-modal/AppointmentModal.jsx"
  "src/components/appointment-modal/ChangeMeetingTypeModal.jsx"
  "src/components/appointment-modal/RescheduleAppointmentModal.jsx"
  "src/redux/slices/locationSlice.js"
  "src/utils/helperFunction.js"
)
for rel in "${WEB_PATCHES[@]}"; do
  copy_if_exists "${HOMES_ROOT}/${rel}" "${BACKUP_DIR}/web/patches/${rel}"
done

echo "==> Database"
ENV_FILE="${ADMIN_ROOT}/.env"
env_val() { grep -E "^${1}=" "${ENV_FILE}" 2>/dev/null | head -1 | cut -d= -f2- | tr -d "\"'\r\n " || true; }
DB_HOST="$(env_val DB_HOST)"; DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="$(env_val DB_PORT)"; DB_PORT="${DB_PORT:-3306}"
DB_NAME="$(env_val DB_DATABASE)"
DB_USER="$(env_val DB_USERNAME)"
DB_PASS="$(env_val DB_PASSWORD)"

mysqldump -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" \
  > "${BACKUP_DIR}/db/full_database.sql"

AREA_TABLES="area_listing_states area_listing_cities area_listing_areas area_listing_sub_areas \
  area_listing_property_locations area_listing_project_locations area_listing_suggestions \
  area_listing_merge_audits"
mysqldump -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" ${AREA_TABLES} \
  > "${BACKUP_DIR}/db/area_listing_tables.sql" 2>/dev/null || true

echo "==> Meta"
{
  echo "Sukoon Homes — Final Complete Backup"
  echo "Created (UTC): $(date -u +'%Y-%m-%d %H:%M:%S')"
  echo "Admin: ${ADMIN_ROOT}"
  echo "Web:   ${HOMES_ROOT}"
  echo ""
  echo "Structure:"
  echo "  admin/plugins/     AreaListing + NearbyPlaces (full)"
  echo "  admin/patches/     Minimal wrteam core files we patched"
  echo "  admin/public-js/   maps-helper.js, property-table-scroll.css"
  echo "  admin/views/       property create|edit|index blades"
  echo "  web/plugins/       area-listing, nearby-places, property-detail-switcher"
  echo "  web/patches/       Minimal Next.js files we patched"
  echo "  db/                full_database.sql + area_listing_tables.sql"
  echo ""
  echo "Restore: see RESTORE.md in local cursr repo"
  echo "Policy: prefer plugins; re-apply admin/patches and web/patches after wrteam updates"
} > "${BACKUP_DIR}/MANIFEST.txt"

php -r 'echo "PHP ".PHP_VERSION."\n";' 2>/dev/null >> "${BACKUP_DIR}/meta/environment.txt" || true
node -v >> "${BACKUP_DIR}/meta/environment.txt" 2>/dev/null || true
pm2 describe homes-sukoon 2>/dev/null | head -20 >> "${BACKUP_DIR}/meta/pm2-homes-sukoon.txt" || true

echo "==> Checksums"
(
  cd "${BACKUP_DIR}"
  find . -type f ! -name 'CHECKSUMS.sha256' -print0 | sort -z | xargs -0 sha256sum
) > "${BACKUP_DIR}/CHECKSUMS.sha256"

echo "==> Archive"
tar -czf "${ARCHIVE}" -C "${ADMIN_ROOT}/storage/backups" "${BACKUP_NAME}"
chown -R www:www "${BACKUP_DIR}" "${ARCHIVE}" 2>/dev/null || true
chmod -R u+rwX,go+rX "${BACKUP_DIR}" "${ARCHIVE}" 2>/dev/null || true

echo ""
echo "BACKUP_DIR=${BACKUP_DIR}"
echo "ARCHIVE=${ARCHIVE}"
echo "SIZE=$(du -sh "${BACKUP_DIR}" "${ARCHIVE}" | awk '{print $1}' | tr '\n' ' ')"
echo "FILES=$(find "${BACKUP_DIR}" -type f | wc -l)"
echo "BACKUP_COMPLETE"

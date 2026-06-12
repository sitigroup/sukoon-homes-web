#!/bin/bash
set -eu

ADMIN_ROOT="/www/wwwroot/admin-homes"
HOMES_ROOT="/www/wwwroot/homes.sukoon.group"
STAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="${ADMIN_ROOT}/storage/backups/area-wise-plugin-complete-${STAMP}"

mkdir -p "${BACKUP_DIR}/plugin/Services"
mkdir -p "${BACKUP_DIR}/plugin/Http/Controllers/Admin"
mkdir -p "${BACKUP_DIR}/plugin/Http/Controllers/Api"
mkdir -p "${BACKUP_DIR}/plugin/views/admin/partials"
mkdir -p "${BACKUP_DIR}/plugin-full"
mkdir -p "${BACKUP_DIR}/nearby"
mkdir -p "${BACKUP_DIR}/controllers"
mkdir -p "${BACKUP_DIR}/frontend/property-detail"
mkdir -p "${BACKUP_DIR}/frontend/user"
mkdir -p "${BACKUP_DIR}/db"

cp -a "${ADMIN_ROOT}/app/Plugins/AreaListing" "${BACKUP_DIR}/plugin-full/"
cp "${ADMIN_ROOT}/app/Plugins/AreaListing/Services/AreaListingService.php" "${BACKUP_DIR}/plugin/Services/"
cp "${ADMIN_ROOT}/app/Plugins/AreaListing/Http/Controllers/Admin/AreaListingAdminController.php" "${BACKUP_DIR}/plugin/Http/Controllers/Admin/"
cp "${ADMIN_ROOT}/app/Plugins/AreaListing/Http/Controllers/Api/AreaListingApiController.php" "${BACKUP_DIR}/plugin/Http/Controllers/Api/"
cp "${ADMIN_ROOT}/app/Plugins/AreaListing/views/admin/partials/listing-location-fields.blade.php" "${BACKUP_DIR}/plugin/views/admin/partials/"
cp "${ADMIN_ROOT}/app/Plugins/NearbyPlaces/Services/NearbyPlacesService.php" "${BACKUP_DIR}/nearby/"
cp "${ADMIN_ROOT}/app/Http/Controllers/ProjectController.php" "${BACKUP_DIR}/controllers/"

if [ -f "${HOMES_ROOT}/src/components/property-detail/PropertyInfoBanner.jsx" ]; then
  cp "${HOMES_ROOT}/src/components/property-detail/PropertyInfoBanner.jsx" "${BACKUP_DIR}/frontend/property-detail/"
fi
if [ -f "${HOMES_ROOT}/src/components/user/EditProperty.jsx" ]; then
  cp "${HOMES_ROOT}/src/components/user/EditProperty.jsx" "${BACKUP_DIR}/frontend/user/"
fi
if [ -f "${HOMES_ROOT}/src/components/user/UserRoot.jsx" ]; then
  cp "${HOMES_ROOT}/src/components/user/UserRoot.jsx" "${BACKUP_DIR}/frontend/user/"
fi

cd "${ADMIN_ROOT}"
ENV_FILE="${ADMIN_ROOT}/.env"
env_val() { grep -E "^${1}=" "${ENV_FILE}" | head -1 | cut -d= -f2- | tr -d "\"'\r\n "; }
DB_HOST=$(env_val DB_HOST)
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=$(env_val DB_PORT)
DB_PORT=${DB_PORT:-3306}
DB_NAME=$(env_val DB_DATABASE)
DB_USER=$(env_val DB_USERNAME)
DB_PASS=$(env_val DB_PASSWORD)

TABLES="area_listing_states area_listing_cities area_listing_areas area_listing_sub_areas area_listing_property_locations area_listing_project_locations area_listing_suggestions area_listing_merge_audits propertys"
mysqldump -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" ${TABLES} > "${BACKUP_DIR}/db/area-listing-tables.sql"

cat > "${BACKUP_DIR}/MANIFEST.txt" << EOF
Area Wise Plugin - Fresh Complete Backup
Created: ${STAMP}

plugin-full/AreaListing/ - full plugin
plugin/ - key patched files
nearby/NearbyPlacesService.php
controllers/ProjectController.php
frontend/ - homes.sukoon.group location UI
db/area-listing-tables.sql
CHECKSUMS.sha256
EOF

cd "${BACKUP_DIR}"
find . -type f ! -name CHECKSUMS.sha256 -print0 | sort -z | xargs -0 sha256sum > CHECKSUMS.sha256

echo "BACKUP_DIR=${BACKUP_DIR}"
echo "FILES=$(find . -type f | wc -l)"
du -sh "${BACKUP_DIR}"

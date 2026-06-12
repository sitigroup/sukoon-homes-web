#!/bin/bash
set -euo pipefail

BACKUP_DIR="/www/wwwroot/admin-homes/storage/backups/area-wise-batch-a-complete-20260520"
ENV_FILE="/www/wwwroot/admin-homes/.env"
APP_ROOT="/www/wwwroot/admin-homes"
DATE_TAG="20260520"
TS="$(date -u +'%Y-%m-%d %H:%M:%S UTC')"

mkdir -p "$BACKUP_DIR/plugin" "$BACKUP_DIR/snapshots"

# Full AreaListing plugin snapshot
cp -a "$APP_ROOT/app/Plugins/AreaListing/." "$BACKUP_DIR/plugin/"

# ProjectController snapshot (Batch A item 2)
cp "$APP_ROOT/app/Http/Controllers/ProjectController.php" \
  "$BACKUP_DIR/snapshots/ProjectController.php"

# Full DB dump
source <(grep -E '^DB_(HOST|PORT|DATABASE|USERNAME|PASSWORD)=' "$ENV_FILE" | sed 's/^/export /')
SQL_OUT="$BACKUP_DIR/sql_admin_homes_full_${DATE_TAG}.sql"
mysqldump -h"${DB_HOST:-127.0.0.1}" -P"${DB_PORT:-3306}" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$SQL_OUT"

# MANIFEST
cat > "$BACKUP_DIR/MANIFEST.txt" <<EOF
Area Wise Batch A Complete Backup
Created: $TS
Host: admin-homes.sukoon.group
Path: $BACKUP_DIR

Batch A items (all deployed):
1. Sub-area similarity check — getSimilarExistingSubArea(), storeSubArea() 409 + force_create,
   listing-location-fields.blade.php Use Existing / Create New Anyway UI
2. Admin project GPS protection — ProjectController update uses
   buildMergedProjectAreaListingRequest() before saveProjectLocation()
3. city_id on inline area create — resolveAreaContext() in areaPayload() / storeArea()

Contents:
- sql_admin_homes_full_${DATE_TAG}.sql (full database)
- plugin/ (full AreaListing plugin snapshot)
- snapshots/ProjectController.php (admin project update merge)

Prior restore point (Items 1-7 hardening): area-wise-hardened-complete-v2-20260520
EOF

# SHA256 checksums (files only; plugin tree via find)
{
  sha256sum "$SQL_OUT"
  sha256sum "$BACKUP_DIR/snapshots/ProjectController.php"
  sha256sum "$BACKUP_DIR/MANIFEST.txt"
  find "$BACKUP_DIR/plugin" -type f -print0 | sort -z | xargs -0 sha256sum
} > "$BACKUP_DIR/CHECKSUMS.sha256"

chown -R www:www "$BACKUP_DIR" 2>/dev/null || true
chmod -R u+rwX,go+rX "$BACKUP_DIR" 2>/dev/null || true

echo "BACKUP_DIR=$BACKUP_DIR"
ls -lh "$SQL_OUT" "$BACKUP_DIR/snapshots/ProjectController.php" "$BACKUP_DIR/MANIFEST.txt" "$BACKUP_DIR/CHECKSUMS.sha256"
wc -l "$BACKUP_DIR/CHECKSUMS.sha256"
echo BACKUP_OK

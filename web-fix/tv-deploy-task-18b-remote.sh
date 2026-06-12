#!/usr/bin/env bash
# Task 18B — run ON server (after plugin tarball + web-fix tarball in /tmp)
set -euo pipefail

ADMIN="/www/wwwroot/admin-homes"
HOMES="/www/wwwroot/homes.sukoon.group"
BACKUP_DIR="$ADMIN/storage/backups/tv-reliability-$(date +%Y%m%d)"
PLUGIN_TGZ="${1:-/tmp/TrustVerification-task18.tgz}"
WEBFIX_TGZ="${2:-/tmp/web-fix-task18.tgz}"

mkdir -p "$BACKUP_DIR"

echo "== 1. Backup plugin =="
tar -czf "$BACKUP_DIR/plugin-TrustVerification-pre-18b.tgz" -C "$ADMIN/app/Plugins" TrustVerification 2>/dev/null || true
ls -la "$BACKUP_DIR/plugin-TrustVerification-pre-18b.tgz"

echo "== 1b. Backup DB (best effort) =="
mysqldump admin_homes 2>/dev/null | gzip -c > "$BACKUP_DIR/sql_admin_homes-pre-18b.sql.gz" || \
  echo "WARN: mysqldump skipped (credentials)"

echo "== 2. Deploy plugin =="
mkdir -p /tmp/tv-plugin-18
tar -xzf "$PLUGIN_TGZ" -C /tmp/tv-plugin-18
SRC="/tmp/tv-plugin-18/TrustVerification"
[[ -d "$SRC" ]] || SRC="/tmp/tv-plugin-18"
rsync -a "${SRC%/}/" "$ADMIN/app/Plugins/TrustVerification/"

echo "== 3. Migrate + clear + refresh =="
cd "$ADMIN"
php artisan migrate --path=app/Plugins/TrustVerification/database/migrations/2026_05_30_000016_create_tv_tenant_reliability_table.php --force
php artisan optimize:clear
php artisan tenant-reliability:refresh

echo "== 4. Permissions =="
chown -R www:www "$ADMIN/app/Plugins/TrustVerification"
find "$ADMIN/app/Plugins/TrustVerification" -type d -exec chmod 755 {} \;
find "$ADMIN/app/Plugins/TrustVerification" -type f -exec chmod 644 {} \;

echo "== 5. Frontend sync =="
mkdir -p /tmp/webfix-18
tar -xzf "$WEBFIX_TGZ" -C /tmp/webfix-18
WF="/tmp/webfix-18/web-fix"
[[ -d "$WF" ]] || WF="/tmp/webfix-18"
TV="$HOMES/src/plugins/trust-verification"
mkdir -p "$TV/ui"
cp "$WF/plugins/trust-verification/trustVerificationApi.js" "$TV/"
cp "$WF/plugins/trust-verification/ui/"*.jsx "$TV/ui/" 2>/dev/null || true
cp "$WF/plugins/trust-verification/ui/"*.js "$TV/ui/" 2>/dev/null || true
cp "$WF/plugins/trust-verification/ui/trustVerificationPremium.module.css" "$TV/ui/"
if [[ -f "$WF/homes-frontend/src/components/owner-details-card/OwnerDetailsCard.jsx" ]]; then
  cp "$WF/homes-frontend/src/components/owner-details-card/OwnerDetailsCard.jsx" \
    "$HOMES/src/components/owner-details-card/OwnerDetailsCard.jsx"
fi
chown -R www:www "$TV" "$HOMES/src/components/owner-details-card" 2>/dev/null || true

echo "== 6. Build homes =="
cd "$HOMES"
export NODE_OPTIONS="${NODE_OPTIONS:---max-old-space-size=4096}"
npm run build
pm2 restart homes-sukoon 2>/dev/null || pm2 restart all 2>/dev/null || true

echo "== 7. Route check =="
php "$ADMIN/artisan" route:list --path=tenant-reliability 2>/dev/null | head -8
php "$ADMIN/artisan" route:list --path=reliability 2>/dev/null | head -12

echo "TASK18B_DEPLOY_DONE"

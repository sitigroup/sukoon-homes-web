#!/usr/bin/env bash
# Rollback Sukoon Theme plugin — run immediately if anything breaks after deploy
#
# Usage:
#   BACKUP_DIR=/path/to/sukoon-final-complete-YYYYMMDD-HHMMSS bash web-fix/rollback-theme-plugin.sh
#
# Or restore from ARCHIVE .tar.gz under admin-homes/storage/backups/

set -euo pipefail

ADMIN="${ADMIN_ROOT:-/www/wwwroot/admin-homes}"
HOMES="${HOMES_ROOT:-/www/wwwroot/homes.sukoon.group}"
PM2_APP="${PM2_APP:-homes-sukoon}"
WEB_USER="${WEB_USER:-www}"
REPO="${REPO:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"

echo "==> ROLLBACK Sukoon Theme Plugin"
echo "Admin: $ADMIN"
echo "Homes: $HOMES"

read -r -p "Continue rollback? [y/N] " confirm
if [[ "${confirm:-n}" != "y" && "${confirm:-n}" != "Y" ]]; then
  echo "Aborted."
  exit 0
fi

log() { echo "==> $*"; }

log "Remove Laravel Theme plugin"
rm -rf "$ADMIN/app/Plugins/Theme"

log "Rollback theme migrations (if applied)"
cd "$ADMIN"
sudo -u "$WEB_USER" php artisan migrate:rollback --force --path=app/Plugins/Theme/database/migrations 2>/dev/null || true

log "Remove ThemeServiceProvider from config/app.php (manual line if patch fails)"
if [[ -f "$ADMIN/config/app.php" ]]; then
  sed -i.bak '/ThemeServiceProvider/d' "$ADMIN/config/app.php" || true
fi

log "Remove Next.js theme plugin"
rm -rf "$HOMES/src/plugins/theme"

log "Clear Laravel cache"
sudo -u "$WEB_USER" php artisan optimize:clear

if [[ -n "${BACKUP_DIR:-}" && -d "$BACKUP_DIR/web/patches" ]]; then
  log "Restore patched web files from BACKUP_DIR"
  cp -a "$BACKUP_DIR/web/patches/"* "$HOMES/" 2>/dev/null || true
fi

if [[ -n "${BACKUP_DIR:-}" && -d "$BACKUP_DIR/admin/patches" ]]; then
  log "Restore patched admin files from BACKUP_DIR"
  for f in "$BACKUP_DIR/admin/patches/"*; do
  [[ -f "$f" ]] || continue
  rel="${f#$BACKUP_DIR/admin/patches/}"
  cp -a "$f" "$ADMIN/$rel" 2>/dev/null || true
  done
fi

log "Rebuild frontend + restart PM2"
cd "$HOMES"
sudo -u "$WEB_USER" npm run build
pm2 restart "$PM2_APP"

echo ""
echo "ROLLBACK COMPLETE."
echo "Verify: curl -s ${HEALTH_BASE_URL:-https://admin-homes.sukoon.group}/api/web-settings | head"
echo "If sidebar/main.blade.php still have theme patches, restore from BACKUP_DIR manually."

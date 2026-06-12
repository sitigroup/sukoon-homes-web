#!/usr/bin/env bash
# Sukoon Theme Plugin — staged production deploy (safe)
#
# Follows pre-production checklist:
#   1 backup → 2 admin deploy (no publish) → 3 migrate → 4 verify API
#   → PAUSE for admin preset test → 7 build → 8 pm2 restart homes-sukoon → 9 verify
#
# Usage (on server):
#   bash web-fix/deploy-theme-plugin-staged.sh backup
#   bash web-fix/deploy-theme-plugin-staged.sh admin
#   bash web-fix/deploy-theme-plugin-staged.sh verify-api
#   # Manual: admin → Appearance → Theme Settings → Apply Sukoon Luxury Black to draft
#   # Manual: Publish when ready for live theme
#   bash web-fix/deploy-theme-plugin-staged.sh frontend
#   bash web-fix/deploy-theme-plugin-staged.sh verify-live
#
#   bash web-fix/deploy-theme-plugin-staged.sh all-through-admin   # steps 1-4 only
#
# NEVER: pm2 restart homes (use homes-sukoon)
# NEVER: php artisan config:cache

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${REPO:-$(cd "$SCRIPT_DIR/.." && pwd)}"
ADMIN="${ADMIN_ROOT:-/www/wwwroot/admin-homes}"
HOMES="${HOMES_ROOT:-/www/wwwroot/homes.sukoon.group}"
PM2_APP="${PM2_APP:-homes-sukoon}"
WEB_USER="${WEB_USER:-www}"
BASE_URL="${HEALTH_BASE_URL:-https://admin-homes.sukoon.group}"
HOMES_URL="${HOMES_URL:-https://homes.sukoon.group}"

PHASE="${1:-help}"

log() { echo ""; echo "==> $*"; }

require_server() {
  if [[ ! -f "$ADMIN/artisan" ]]; then
    echo "ERROR: admin-homes not found at $ADMIN (run on server)" >&2
    exit 1
  fi
}

phase_backup() {
  log "STEP 1 — Full backup (admin + homes + database)"
  bash "$REPO/scripts/final-sukoon-complete-backup.sh"
  echo ""
  echo "BACKUP DONE. Note BACKUP_DIR / ARCHIVE from output above before continuing."
}

phase_admin() {
  require_server
  log "STEP 2 — Deploy Theme plugin (admin only, NOT publishing theme)"

  mkdir -p "$ADMIN/app/Plugins/Theme"
  rsync -a --delete "$REPO/plugins/Theme/" "$ADMIN/app/Plugins/Theme/"
  chown -R "$WEB_USER:$WEB_USER" "$ADMIN/app/Plugins/Theme"
  find "$ADMIN/app/Plugins/Theme" -type d -exec chmod 755 {} \;
  find "$ADMIN/app/Plugins/Theme" -type f -exec chmod 644 {} \;

  cd "$ADMIN"
  php "$REPO/web-fix/patch-theme-register.php" || true
  php "$REPO/web-fix/patch-theme-sidebar.php" || true
  php "$REPO/web-fix/patch-theme-admin-head.php" || true

  log "STEP 3 — Run migrations"
  sudo -u "$WEB_USER" php artisan migrate --force --path=app/Plugins/Theme/database/migrations

  log "Initialize draft defaults (does NOT publish)"
  sudo -u "$WEB_USER" php artisan tinker --execute="\\App\\Plugins\\Theme\\Services\\ThemeService::setting();"

  chown -R "$WEB_USER:$WEB_USER" "$ADMIN/bootstrap/cache" 2>/dev/null || true
  sudo -u "$WEB_USER" php artisan optimize:clear

  echo ""
  echo "ADMIN DEPLOY COMPLETE — theme NOT published (live site unchanged)."
}

phase_verify_api() {
  log "STEP 4 — Verify GET /api/theme/public"
  local url="${BASE_URL}/api/theme/public"
  local body
  body="$(curl -fsS "$url")"
  echo "$body" | head -c 500
  echo ""

  if echo "$body" | grep -q '"published":false'; then
    echo "PASS: API returns published=false (safe — no live theme yet)"
  elif echo "$body" | grep -q '"published":true'; then
    echo "WARN: Theme already published on server — skip admin publish until tested"
  else
    echo "FAIL: Unexpected API response — check route registration and plugin deploy" >&2
    exit 1
  fi

  echo ""
  echo "STEP 5-6 — MANUAL (do before frontend hooks):"
  echo "  1. Open admin: ${BASE_URL}/appearance/theme"
  echo "  2. Appearance → Theme Settings"
  echo "  3. Click 'Apply to draft' on preset: Sukoon Luxury Black"
  echo "  4. Confirm live preview looks correct"
  echo "  5. Do NOT click Publish until frontend phase is ready and tested"
  echo ""
  echo "When ready for live theme: Publish in admin, then run:"
  echo "  bash web-fix/deploy-theme-plugin-staged.sh frontend"
}

phase_frontend() {
  require_server
  if [[ ! -d "$HOMES/package.json" && ! -f "$HOMES/package.json" ]]; then
    echo "ERROR: homes frontend not found at $HOMES" >&2
    exit 1
  fi

  log "Deploy Next.js theme plugin files"
  mkdir -p "$HOMES/src/plugins/theme"
  rsync -a --delete "$REPO/web-fix/plugins/theme/" "$HOMES/src/plugins/theme/"
  chown -R "$WEB_USER:$WEB_USER" "$HOMES/src/plugins/theme"

  log "Apply minimal Next.js hooks (_app + Layout)"
  HOMES_ROOT="$HOMES" node "$REPO/web-fix/patch-theme-app.js" || true
  HOMES_ROOT="$HOMES" node "$REPO/web-fix/patch-theme-layout.js" || true
  HOMES_ROOT="$HOMES" node "$REPO/web-fix/patch-trust-verification-tailwind-content.js" 2>/dev/null || true

  log "STEP 7 — npm run build"
  cd "$HOMES"
  npm run build

  log "STEP 8 — pm2 restart $PM2_APP (NOT homes)"
  pm2 restart "$PM2_APP"
  pm2 describe "$PM2_APP" | head -15

  echo ""
  echo "FRONTEND DEPLOY COMPLETE."
  echo "Run: bash web-fix/deploy-theme-plugin-staged.sh verify-live"
}

phase_verify_live() {
  log "STEP 9 — Verify live site + critical flows"
  bash "$SCRIPT_DIR/verify-theme-production.sh"
}

phase_all_through_admin() {
  phase_backup
  phase_admin
  phase_verify_api
}

phase_help() {
  cat <<EOF
Sukoon Theme — staged deploy

Phases:
  backup              Step 1 — full backup
  admin               Steps 2-3 — deploy plugin + migrate (no publish)
  verify-api          Step 4 — curl /api/theme/public
  frontend            Steps 7-8 — copy plugin, build, pm2 restart homes-sukoon
  verify-live         Step 9 — smoke tests
  all-through-admin   backup + admin + verify-api (stops before manual admin)

Rollback:
  bash web-fix/rollback-theme-plugin.sh

PM2 app: $PM2_APP (default homes-sukoon)
EOF
}

case "$PHASE" in
  backup) phase_backup ;;
  admin) phase_admin ;;
  verify-api) phase_verify_api ;;
  frontend) phase_frontend ;;
  verify-live) phase_verify_live ;;
  all-through-admin) phase_all_through_admin ;;
  help|-h|--help) phase_help ;;
  *)
    echo "Unknown phase: $PHASE" >&2
    phase_help
    exit 1
    ;;
esac

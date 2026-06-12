#!/usr/bin/env bash
# Task 18 — deploy tenant reliability (admin plugin + homes frontend)
set -euo pipefail

ADMIN="/www/wwwroot/admin-homes"
HOMES="/www/wwwroot/homes.sukoon.group"
REPO="${REPO:-/root/cursr}"
PLUGIN_SRC="${PLUGIN_SRC:-$REPO/plugins/TrustVerification}"
WEB_FIX="${WEB_FIX:-$REPO/web-fix}"

echo "== Task 18: sync TrustVerification plugin =="
rsync -a --delete \
  --exclude='.git' \
  "$PLUGIN_SRC/" "$ADMIN/app/Plugins/TrustVerification/"

echo "== Migrate tv_tenant_reliability =="
cd "$ADMIN"
php artisan migrate --path=app/Plugins/TrustVerification/database/migrations/2026_05_30_000016_create_tv_tenant_reliability_table.php --force
php artisan optimize:clear
php artisan tenant-reliability:refresh

echo "== Sync homes trust-verification UI =="
TV="$HOMES/src/plugins/trust-verification"
mkdir -p "$TV/ui"
cp "$WEB_FIX/plugins/trust-verification/trustVerificationApi.js" "$TV/"
cp "$WEB_FIX/plugins/trust-verification/ui/"*.jsx "$TV/ui/" 2>/dev/null || true
cp "$WEB_FIX/plugins/trust-verification/ui/"*.js "$TV/ui/" 2>/dev/null || true
cp "$WEB_FIX/plugins/trust-verification/ui/trustVerificationPremium.module.css" "$TV/ui/"

if [[ -f "$WEB_FIX/homes-frontend/src/components/owner-details-card/OwnerDetailsCard.jsx" ]]; then
  cp "$WEB_FIX/homes-frontend/src/components/owner-details-card/OwnerDetailsCard.jsx" \
    "$HOMES/src/components/owner-details-card/OwnerDetailsCard.jsx"
fi

chown -R www:www "$ADMIN/app/Plugins/TrustVerification" "$TV" 2>/dev/null || true

echo "== Build homes =="
cd "$HOMES"
export NODE_OPTIONS="${NODE_OPTIONS:---max-old-space-size=4096}"
npm run build
pm2 restart homes-sukoon 2>/dev/null || pm2 restart all 2>/dev/null || true

echo "Task 18 deploy finished."

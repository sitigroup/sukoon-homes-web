#!/usr/bin/env bash
set -euo pipefail
HOMES=/www/wwwroot/homes.sukoon.group
ADMIN=/www/wwwroot/admin-homes
WORKDIR=/tmp/tv-cms-12b-extract
rm -rf "$WORKDIR"
mkdir -p "$WORKDIR"
tar -xzf /tmp/tv-cms-12b-deploy.tgz -C "$WORKDIR"

PLUGIN_SRC="$WORKDIR/plugins/trust-verification"
PLUGIN_DST="$HOMES/src/plugins/trust-verification"
mkdir -p "$PLUGIN_DST" "$PLUGIN_DST/ui"
rsync -a "$PLUGIN_SRC/" "$PLUGIN_DST/"

mkdir -p "$HOMES/pages/verification-terms" "$HOMES/pages/verification-privacy" "$HOMES/pages/verification-refund-policy"
cp "$WORKDIR/pages-verification-terms.jsx" "$HOMES/pages/verification-terms/index.jsx"
cp "$WORKDIR/pages-verification-privacy.jsx" "$HOMES/pages/verification-privacy/index.jsx"
cp "$WORKDIR/pages-verification-refund-policy.jsx" "$HOMES/pages/verification-refund-policy/index.jsx"

chown -R www:www "$PLUGIN_DST" "$HOMES/pages/verification-terms" "$HOMES/pages/verification-privacy" "$HOMES/pages/verification-refund-policy" 2>/dev/null || true

cd "$HOMES"
npm run build
pm2 restart homes-sukoon 2>/dev/null || true

cd "$ADMIN"
php artisan optimize:clear

echo "DEPLOY_OK"

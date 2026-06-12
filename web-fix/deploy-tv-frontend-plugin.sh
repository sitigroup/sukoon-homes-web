#!/usr/bin/env bash
set -euo pipefail
HOMES=/www/wwwroot/homes.sukoon.group
WORKDIR=/tmp/tv-frontend-extract
rm -rf "$WORKDIR"
mkdir -p "$WORKDIR"
tar -xzf /tmp/tv-frontend-deploy.tgz -C "$WORKDIR"

PLUGIN_SRC="$WORKDIR/plugins/trust-verification"
PLUGIN_DST="$HOMES/src/plugins/trust-verification"
mkdir -p "$PLUGIN_DST" "$PLUGIN_DST/ui"
rsync -a "$PLUGIN_SRC/" "$PLUGIN_DST/"

chown -R www:www "$PLUGIN_DST" 2>/dev/null || true

cd "$HOMES"
npm run build
pm2 restart homes-sukoon

echo "DEPLOY_OK"

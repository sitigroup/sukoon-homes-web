#!/usr/bin/env bash
# Task 12B — Deploy Trust Verification CMS frontend to homes.sukoon.group
set -euo pipefail
HOMES=/www/wwwroot/homes.sukoon.group
FIX="$(cd "$(dirname "$0")" && pwd)"
PLUGIN_SRC="$FIX/plugins/trust-verification"
PLUGIN_DST="$HOMES/src/plugins/trust-verification"

echo "==> Sync plugin (trust-verification)"
mkdir -p "$PLUGIN_DST" "$PLUGIN_DST/ui"
rsync -a --delete \
  --exclude 'node_modules' \
  "$PLUGIN_SRC/" "$PLUGIN_DST/"

echo "==> Legal pages"
cp "$FIX/pages-verification-terms.jsx" "$HOMES/src/pages/verification-terms/index.jsx"
cp "$FIX/pages-verification-privacy.jsx" "$HOMES/src/pages/verification-privacy/index.jsx"
cp "$FIX/pages-verification-refund-policy.jsx" "$HOMES/src/pages/verification-refund-policy/index.jsx"

echo "==> npm run build"
cd "$HOMES"
npm run build

echo "==> pm2 restart"
pm2 restart homes-sukoon 2>/dev/null || pm2 restart all 2>/dev/null || true

echo "Task 12B frontend deploy complete."

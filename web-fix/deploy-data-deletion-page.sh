#!/usr/bin/env bash
# Deploy public /data-deletion page (Meta App Review) to homes.sukoon.group
# On Windows dev machine, use: bash deploy-data-deletion-remote.sh
# On server: run this script directly.
set -euo pipefail
HOMES="${HOMES_ROOT:-/www/wwwroot/homes.sukoon.group}"
FIX="$(cd "$(dirname "$0")" && pwd)"

echo "==> Data deletion page"
mkdir -p "$HOMES/pages/data-deletion" "$HOMES/src/components/legal"
cp "$FIX/pages-data-deletion.jsx" "$HOMES/pages/data-deletion/index.jsx"
cp "$FIX/components/legal/DataDeletionInstructions.jsx" "$HOMES/src/components/legal/DataDeletionInstructions.jsx"
cp "$FIX/components/legal/dataDeletionContent.js" "$HOMES/src/components/legal/dataDeletionContent.js"
chown -R www:www "$HOMES/pages/data-deletion" "$HOMES/src/components/legal" 2>/dev/null || true

echo "==> npm run build"
cd "$HOMES"
npm run build

echo "==> pm2 restart (if configured)"
pm2 restart homes-sukoon 2>/dev/null || pm2 restart all 2>/dev/null || true

code=$(curl -sL -o /dev/null -w "%{http_code}" "https://homes.sukoon.group/data-deletion?lang=en" || echo "000")
echo "Verify: https://homes.sukoon.group/data-deletion => HTTP $code"
[ "$code" = "200" ] || exit 1
echo "DEPLOY_OK"

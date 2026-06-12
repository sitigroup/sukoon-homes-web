#!/usr/bin/env bash
# Deploy /data-deletion to production via SSH (same host as other web-fix deploys).
set -euo pipefail
HOST="${DEPLOY_HOST:-srv1534644}"
BASE="${HOMES_ROOT:-/www/wwwroot/homes.sukoon.group}"
FIX="$(cd "$(dirname "$0")" && pwd)"

echo "==> Copy data-deletion page to $HOST:$BASE"
ssh -o ConnectTimeout=25 "$HOST" "mkdir -p '$BASE/pages/data-deletion' '$BASE/src/components/legal'"
scp -o ConnectTimeout=25 "$FIX/pages-data-deletion.jsx" "$HOST:$BASE/pages/data-deletion/index.jsx"
scp -o ConnectTimeout=25 "$FIX/components/legal/DataDeletionInstructions.jsx" "$HOST:$BASE/src/components/legal/DataDeletionInstructions.jsx"
scp -o ConnectTimeout=25 "$FIX/components/legal/dataDeletionContent.js" "$HOST:$BASE/src/components/legal/dataDeletionContent.js"

echo "==> Build + restart on server"
ssh -o ConnectTimeout=25 "$HOST" "chown -R www:www '$BASE/pages/data-deletion' '$BASE/src/components/legal' 2>/dev/null || true; cd '$BASE' && npm run build 2>&1 | tail -20 && (pm2 restart homes-sukoon 2>/dev/null || pm2 restart all 2>/dev/null || true)"

echo "==> HTTP check"
code=$(curl -sL -o /dev/null -w "%{http_code}" "https://homes.sukoon.group/data-deletion?lang=en" || echo "000")
echo "https://homes.sukoon.group/data-deletion => HTTP $code"
if [ "$code" != "200" ]; then
  echo "WARN: expected 200 — check build log on server"
  exit 1
fi
echo "DEPLOY_OK"

#!/usr/bin/env bash
# TASK A1 — robots.txt + noindex hygiene (homes.sukoon.group)
set -euo pipefail
HOST="${DEPLOY_HOST:-srv1534644-ipv4}"
BASE="${HOMES_ROOT:-/www/wwwroot/homes.sukoon.group}"
FIX="$(cd "$(dirname "$0")" && pwd)"

echo "==> Backup originals on $HOST"
ssh -o ConnectTimeout=25 "$HOST" "
  cd '$BASE'
  cp -a public/robots.txt public/robots.txt.backup-a1 2>/dev/null || true
  cp -a src/components/meta/MetaData.jsx src/components/meta/MetaData.jsx.backup-a1 2>/dev/null || true
  cp -a pages/404.jsx pages/404.jsx.backup-a1 2>/dev/null || true
  cp -a pages/login/index.jsx pages/login/index.jsx.backup-a1 2>/dev/null || true
"

echo "==> Copy A1 files"
scp -o ConnectTimeout=25 \
  "$FIX/robots.txt" "$HOST:$BASE/public/robots.txt"
scp -o ConnectTimeout=25 \
  "$FIX/MetaData.jsx" "$HOST:$BASE/src/components/meta/MetaData.jsx"
scp -o ConnectTimeout=25 \
  "$FIX/404.jsx" "$HOST:$BASE/pages/404.jsx"
scp -o ConnectTimeout=25 \
  "$FIX/login-index.jsx" "$HOST:$BASE/pages/login/index.jsx"
scp -o ConnectTimeout=25 \
  "$FIX/.env.example" "$HOST:$BASE/.env.example"

echo "==> Verify NEXT_PUBLIC_SEO in production .env"
ssh -o ConnectTimeout=25 "$HOST" "
  grep -E '^NEXT_PUBLIC_SEO=' '$BASE/.env' || echo 'WARN: NEXT_PUBLIC_SEO not set'
"

echo "==> Build + restart"
ssh -o ConnectTimeout=25 "$HOST" "
  chown -R www:www '$BASE/public/robots.txt' '$BASE/src/components/meta/MetaData.jsx' '$BASE/pages/404.jsx' '$BASE/pages/login/index.jsx' '$BASE/.env.example' 2>/dev/null || true
  cd '$BASE' && npm run build 2>&1 | tail -30
  pm2 restart homes-sukoon 2>/dev/null || pm2 restart all 2>/dev/null || true
"

echo "==> VERIFY robots.txt"
curl -sL "https://homes.sukoon.group/robots.txt" | head -20

echo "==> VERIFY login noindex"
curl -sL "https://homes.sukoon.group/login?lang=en" | grep -i 'robots' | head -3 || true

echo "==> VERIFY owner-dashboard noindex"
curl -sL "https://homes.sukoon.group/owner-dashboard?lang=en" | grep -i 'robots' | head -3 || true

echo "DEPLOY_A1_OK"

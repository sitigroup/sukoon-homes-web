#!/usr/bin/env bash
# TASK A2 — sitemap index + location URLs + slug fixes
set -euo pipefail
HOST="${DEPLOY_HOST:-srv1534644-ipv4}"
BASE="${HOMES_ROOT:-/www/wwwroot/homes.sukoon.group}"
FIX="$(cd "$(dirname "$0")" && pwd)"

echo "==> Backup A2 originals"
ssh -o ConnectTimeout=25 "$HOST" "
  cd '$BASE'
  cp -a scripts/sitemap-generator.js scripts/sitemap-generator.js.backup-a2 2>/dev/null || true
  cp -a pages/sitemap.xml.js pages/sitemap.xml.js.backup-a2 2>/dev/null || true
  cp -a src/utils/locationSearchUrl.js src/utils/locationSearchUrl.js.backup-a2 2>/dev/null || true
  mkdir -p pages/sitemaps
"

echo "==> Copy A2 files"
scp -o ConnectTimeout=25 "$FIX/sitemap-generator.js" "$HOST:$BASE/scripts/sitemap-generator.js"
scp -o ConnectTimeout=25 "$FIX/sitemap.xml.js" "$HOST:$BASE/pages/sitemap.xml.js"
scp -o ConnectTimeout=25 "$FIX/sitemaps-[name].js" "$HOST:$BASE/pages/sitemaps/[name].js"
scp -o ConnectTimeout=25 "$FIX/locationSearchUrl.js" "$HOST:$BASE/src/utils/locationSearchUrl.js"
scp -o ConnectTimeout=25 "$FIX/check-slug-consistency.js" "$HOST:$BASE/scripts/check-slug-consistency.js"

echo "==> Build + restart"
ssh -o ConnectTimeout=25 "$HOST" "
  chown -R www:www '$BASE/scripts/sitemap-generator.js' '$BASE/pages/sitemap.xml.js' '$BASE/pages/sitemaps' '$BASE/src/utils/locationSearchUrl.js' '$BASE/scripts/check-slug-consistency.js' 2>/dev/null || true
  cd '$BASE' && npm run build 2>&1 | tail -15
  pm2 restart homes-sukoon 2>/dev/null || pm2 restart all 2>/dev/null || true
"

echo "==> Slug consistency report"
ssh -o ConnectTimeout=25 "$HOST" "cd '$BASE' && node scripts/check-slug-consistency.js 2>&1 | head -40"

echo "DEPLOY_A2_OK"

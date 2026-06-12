#!/usr/bin/env bash
# TASK B5 — /rent/ frontend + dynamic robots/llms
set -euo pipefail
HOST="${DEPLOY_HOST:-srv1534644-ipv4}"
HOMES="${HOMES_ROOT:-/www/wwwroot/homes.sukoon.group}"
ADMIN="${ADMIN_ROOT:-/www/wwwroot/admin-homes}"
B5="$(cd "$(dirname "$0")" && pwd)"
PLUGIN="$(cd "$(dirname "$0")/../../plugins/SeoEngine/src" && pwd)"

echo "==> Sync SeoEngine plugin (bot files API + 404 log + generator hardening)"
ssh -o ConnectTimeout=25 "$HOST" "mkdir -p '$ADMIN/app/Plugins/SeoEngine'"
rsync -avz --delete -e "ssh -o ConnectTimeout=25" \
  --exclude '.git' \
  "$PLUGIN/" "$HOST:$ADMIN/app/Plugins/SeoEngine/"
ssh -o ConnectTimeout=25 "$HOST" "chown -R www:www '$ADMIN/app/Plugins/SeoEngine' && cd '$ADMIN' && php artisan optimize:clear"

echo "==> Deploy Next.js rent pages + bot files"
ssh -o ConnectTimeout=25 "$HOST" "mkdir -p '$HOMES/pages/rent/[[...segments]]' '$HOMES/src/plugins/seo-engine'"
scp -o ConnectTimeout=25 "$B5/rent-[[...segments]].jsx" "$HOST:$HOMES/pages/rent/[[...segments]]/index.jsx"
scp -o ConnectTimeout=25 "$B5/RentPageView.jsx" "$B5/RentPageComponents.jsx" "$B5/rentPageApi.js" "$B5/jsonld-rent.js" "$HOST:$HOMES/src/plugins/seo-engine/"
scp -o ConnectTimeout=25 "$B5/robots.txt.js" "$HOST:$HOMES/pages/robots.txt.js"
scp -o ConnectTimeout=25 "$B5/llms.txt.js" "$HOST:$HOMES/pages/llms.txt.js"
ssh -o ConnectTimeout=25 "$HOST" "chown -R www:www '$HOMES/pages/rent' '$HOMES/pages/robots.txt.js' '$HOMES/pages/llms.txt.js' '$HOMES/src/plugins/seo-engine'"

echo "==> Regenerate registry + purge junk paths"
scp -o ConnectTimeout=25 "$B5/regen-and-purge.php" "$HOST:/tmp/regen-and-purge.php"
ssh -o ConnectTimeout=25 "$HOST" "php /tmp/regen-and-purge.php && chown -R www:www '$ADMIN/storage/app/seo-engine'"

echo "==> Remove static public/robots.txt (dynamic pages/robots.txt.js)"
ssh -o ConnectTimeout=25 "$HOST" "cd '$HOMES' && cp -a public/robots.txt public/robots.txt.backup-b5 2>/dev/null || true && rm -f public/robots.txt"
ssh -o ConnectTimeout=25 "$HOST" "cd '$HOMES' && npm run build 2>&1 | tail -12 && pm2 restart homes-sukoon"

echo "DONE B5 deploy"

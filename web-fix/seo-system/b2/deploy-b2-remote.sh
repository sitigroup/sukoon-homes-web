#!/usr/bin/env bash
# TASK B2 — SeoEngine redirects + middleware
set -euo pipefail
HOST="${DEPLOY_HOST:-srv1534644-ipv4}"
ADMIN="${ADMIN_ROOT:-/www/wwwroot/admin-homes}"
HOMES="${HOMES_ROOT:-/www/wwwroot/homes.sukoon.group}"
SRC="$(cd "$(dirname "$0")/../../plugins/SeoEngine/src" && pwd)"
B2="$(cd "$(dirname "$0")" && pwd)"

echo "==> Sync SeoEngine plugin"
ssh -o ConnectTimeout=25 "$HOST" "mkdir -p '$ADMIN/app/Plugins/SeoEngine'"
cd "$(dirname "$SRC")" && zip -qr /tmp/SeoEngine-b2.zip src
scp -o ConnectTimeout=25 /tmp/SeoEngine-b2.zip "$HOST:/tmp/SeoEngine-b2.zip"
ssh -o ConnectTimeout=25 "$HOST" "cd '$ADMIN/app/Plugins/SeoEngine' && unzip -o /tmp/SeoEngine-b2.zip && rm /tmp/SeoEngine-b2.zip && chown -R www:www '$ADMIN/app/Plugins/SeoEngine'"

echo "==> Deploy Next.js middleware"
ssh -o ConnectTimeout=25 "$HOST" "cp -a '$HOMES/middleware.js' '$HOMES/middleware.js.backup-b2' 2>/dev/null || true"
scp -o ConnectTimeout=25 "$B2/middleware.js" "$HOST:$HOMES/middleware.js"
ssh -o ConnectTimeout=25 "$HOST" "chown www:www '$HOMES/middleware.js'"

echo "==> Laravel optimize + tests"
scp -o ConnectTimeout=25 "$B2/test-redirect-service.php" "$HOST:/tmp/test-redirect-service.php"
ssh -o ConnectTimeout=25 "$HOST" "cd '$ADMIN' && php artisan optimize:clear && php /tmp/test-redirect-service.php"

echo "==> Next.js build + PM2"
ssh -o ConnectTimeout=25 "$HOST" "cd '$HOMES' && npm run build 2>&1 | tail -8 && pm2 restart homes-sukoon"

echo "DONE B2 deploy"

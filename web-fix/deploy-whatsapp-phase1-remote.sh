#!/usr/bin/env bash
set -euo pipefail

HOST="${DEPLOY_HOST:-srv1534644}"
ADMIN="${ADMIN_ROOT:-/www/wwwroot/admin-homes}"
REPO="${REPO_ROOT:-/root/cursr}"
SRC_LOCAL="$(cd "$(dirname "$0")" && pwd)/plugins/Whatsapp/src"
TMP="/tmp/wa-phase1"

echo "==> Upload plugin files"
ssh "$HOST" "rm -rf '$TMP' && mkdir -p '$TMP'"
scp -r "$SRC_LOCAL/"* "$HOST:$TMP/"

echo "==> Install into app/Plugins/Whatsapp"
ssh "$HOST" "mkdir -p '$ADMIN/app/Plugins/Whatsapp' && rsync -a --delete '$TMP/' '$ADMIN/app/Plugins/Whatsapp/' && chown -R www:www '$ADMIN/app/Plugins/Whatsapp'"

echo "==> Register provider with backup"
scp "$(cd "$(dirname "$0")" && pwd)/patch-whatsapp-register.php" "$HOST:$TMP/patch-whatsapp-register.php"
ssh "$HOST" "cd '$ADMIN' && php '$TMP/patch-whatsapp-register.php'"

echo "==> Migrate + clear"
ssh "$HOST" "cd '$ADMIN' && php artisan migrate --force && php artisan optimize:clear"

echo "==> Ensure queue worker"
ssh "$HOST" "pm2 delete sukoon-whatsapp-queue >/dev/null 2>&1 || true; pm2 start \"php artisan queue:work database --queue=whatsapp --sleep=3 --tries=3 --timeout=90\" --name sukoon-whatsapp-queue --cwd '$ADMIN'; pm2 save"

echo "DONE: webhook => https://admin-homes.sukoon.group/api/whatsapp/webhook"


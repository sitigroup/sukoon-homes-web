#!/usr/bin/env bash
set -euo pipefail

ADMIN="${ADMIN_ROOT:-/www/wwwroot/admin-homes}"
REPO="${REPO_ROOT:-/root/cursr}"
SRC="$REPO/web-fix/plugins/Whatsapp/src"

if [[ ! -d "$SRC" ]]; then
  echo "Missing source: $SRC"
  exit 1
fi

mkdir -p "$ADMIN/app/Plugins/Whatsapp"
rsync -a --delete "$SRC/" "$ADMIN/app/Plugins/Whatsapp/"
chown -R www:www "$ADMIN/app/Plugins/Whatsapp" 2>/dev/null || true

cd "$ADMIN"
php "$REPO/web-fix/patch-whatsapp-register.php"
php artisan migrate --force
php artisan optimize:clear

pm2 delete sukoon-whatsapp-queue >/dev/null 2>&1 || true
pm2 start "php artisan queue:work database --queue=whatsapp --sleep=3 --tries=3 --timeout=90" --name sukoon-whatsapp-queue --cwd "$ADMIN"
pm2 save

echo "PHASE 1 deploy complete."
echo "Webhook URL: https://admin-homes.sukoon.group/api/whatsapp/webhook"


#!/usr/bin/env bash
# TASK B1 — SeoEngine plugin scaffold
set -euo pipefail
HOST="${DEPLOY_HOST:-srv1534644-ipv4}"
ADMIN="${ADMIN_ROOT:-/www/wwwroot/admin-homes}"
SRC="$(cd "$(dirname "$0")/../../plugins/SeoEngine/src" && pwd)"
REMOTE_PLUGIN="$ADMIN/app/Plugins/SeoEngine"
PATCH_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"

echo "==> Sync SeoEngine plugin to $HOST:$REMOTE_PLUGIN"
ssh -o ConnectTimeout=25 "$HOST" "mkdir -p '$REMOTE_PLUGIN'"

rsync -avz --delete -e "ssh -o ConnectTimeout=25" \
  --exclude '.git' \
  "$SRC/" "$HOST:$REMOTE_PLUGIN/"

echo "==> Fix ownership (PHP-FPM runs as www)"
ssh -o ConnectTimeout=25 "$HOST" "chown -R www:www '$REMOTE_PLUGIN' && find '$REMOTE_PLUGIN' -type d -exec chmod 755 {} \\; && find '$REMOTE_PLUGIN' -type f -exec chmod 644 {} \\;"

echo "==> Register provider + sidebar (idempotent patches)"
scp -o ConnectTimeout=25 "$PATCH_ROOT/patch-seo-engine-register.php" "$HOST:$ADMIN/patch-seo-engine-register.php"
scp -o ConnectTimeout=25 "$PATCH_ROOT/patch-seo-engine-sidebar.php" "$HOST:$ADMIN/patch-seo-engine-sidebar.php"
ssh -o ConnectTimeout=25 "$HOST" "cd '$ADMIN' && php patch-seo-engine-register.php && python3 /tmp/patch-sidebar-remote.py 2>/dev/null || php patch-seo-engine-sidebar.php"

echo "==> DB backup before migrations"
BACKUP_NAME="seo-engine-b1-$(date +%Y%m%d-%H%M%S).sql.gz"
ssh -o ConnectTimeout=25 "$HOST" "cd '$ADMIN' && bash -lc '
  DB=\$(grep DB_DATABASE .env | cut -d= -f2 | tr -d \"\\\"\\047\")
  USER=\$(grep DB_USERNAME .env | cut -d= -f2 | tr -d \"\\\"\\047\")
  PASS=\$(grep DB_PASSWORD .env | cut -d= -f2 | tr -d \"\\\"\\047\")
  mysqldump -u\"\$USER\" -p\"\$PASS\" \"\$DB\" | gzip > /www/backup/\$BACKUP_NAME 2>/dev/null || mysqldump -u\"\$USER\" -p\"\$PASS\" \"\$DB\" > /www/backup/\${BACKUP_NAME%.gz} 2>/dev/null
  ls -la /www/backup/*seo-engine-b1* 2>/dev/null | tail -1
' BACKUP_NAME=$BACKUP_NAME" || echo "WARN: backup path may differ — verify manually"

echo "==> Migrate + lint + clear caches"
ssh -o ConnectTimeout=25 "$HOST" "cd '$ADMIN' && \
  php -l app/Plugins/SeoEngine/SeoEngineServiceProvider.php && \
  php artisan migrate --path=app/Plugins/SeoEngine/database/migrations --force && \
  php artisan optimize:clear"

echo "==> Verify public API"
ssh -o ConnectTimeout=25 "$HOST" "curl -s 'https://admin-homes.sukoon.group/api/seo-engine/settings' | head -c 500"
echo ""
echo "DONE. Backup: $BACKUP_NAME"

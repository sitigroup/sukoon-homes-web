#!/usr/bin/env bash
# TASK B6 — AI content engine
set -euo pipefail
HOST="${DEPLOY_HOST:-srv1534644-ipv4}"
ADMIN="${ADMIN_ROOT:-/www/wwwroot/admin-homes}"
PLUGIN="$(cd "$(dirname "$0")/../../plugins/SeoEngine/src" && pwd)"

echo "==> Sync SeoEngine plugin (B6 content engine)"
ssh -o ConnectTimeout=25 "$HOST" "mkdir -p '$ADMIN/app/Plugins/SeoEngine'"
rsync -avz --delete -e "ssh -o ConnectTimeout=25" \
  --exclude '.git' \
  "$PLUGIN/" "$HOST:$ADMIN/app/Plugins/SeoEngine/"
ssh -o ConnectTimeout=25 "$HOST" "chown -R www:www '$ADMIN/app/Plugins/SeoEngine' && cd '$ADMIN' && php artisan migrate --path=app/Plugins/SeoEngine/database/migrations --force && php artisan optimize:clear"

echo "DONE B6 deploy"

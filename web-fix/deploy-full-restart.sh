#!/usr/bin/env bash
set -euo pipefail

HOMES=/www/wwwroot/homes.sukoon.group
ADMIN=/www/wwwroot/admin-homes
PLUGIN_DST="$ADMIN/app/Plugins/TrustVerification"

echo "=== FRONTEND: deploy plugin ==="
WORKDIR=/tmp/tv-frontend-extract
rm -rf "$WORKDIR"
mkdir -p "$WORKDIR"
tar -xzf /tmp/tv-frontend-deploy.tgz -C "$WORKDIR"
rsync -a "$WORKDIR/plugins/trust-verification/" "$HOMES/src/plugins/trust-verification/"
chown -R www:www "$HOMES/src/plugins/trust-verification"

echo "=== FRONTEND: npm run build ==="
cd "$HOMES"
npm run build
echo "BUILD_OK"

echo "=== FRONTEND: pm2 restart ==="
pm2 restart homes-sukoon
pm2 status

echo "=== BACKEND: admin CMS views ==="
rsync -a /tmp/tv-admin-cms-deploy/content/ "$PLUGIN_DST/views/admin/content/"
chown -R www:www "$PLUGIN_DST/views/admin/content"
chmod -R 755 "$PLUGIN_DST/views/admin/content"

echo "=== BACKEND: artisan clear ==="
cd "$ADMIN"
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "=== BACKEND: migrate (if any) ==="
php artisan migrate --force --no-interaction 2>&1 | tail -5 || true

echo "=== BACKEND: php-fpm restart ==="
systemctl restart php-fpm-83
systemctl is-active php-fpm-83

echo "DEPLOY_ALL_OK"

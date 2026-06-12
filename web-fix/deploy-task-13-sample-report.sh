#!/usr/bin/env bash
set -euo pipefail
ADMIN=/www/wwwroot/admin-homes
HOMES=/www/wwwroot/homes.sukoon.group
PLUGIN="$ADMIN/app/Plugins/TrustVerification"

echo "=== Backend plugin ==="
cd /tmp
tar -xzf tv-task13-plugin.tgz
rsync -a TrustVerification/ "$PLUGIN/"
chown -R www:www "$PLUGIN"

cd "$ADMIN"
php artisan migrate --force --no-interaction
php artisan view:clear
php artisan route:clear

echo "=== Frontend plugin ==="
tar -xzf /tmp/tv-task13-frontend.tgz -C /tmp
rsync -a /tmp/trust-verification/ "$HOMES/src/plugins/trust-verification/"
chown -R www:www "$HOMES/src/plugins/trust-verification"

cd "$HOMES"
npm run build
pm2 restart homes-sukoon

systemctl restart php-fpm-83

php -r "echo class_exists('App\\Plugins\\TrustVerification\\Models\\TvSampleReport') ? 'MODEL_OK' : 'MODEL_MISSING'; echo PHP_EOL;"
test -f "$PLUGIN/views/admin/sample-reports/index.blade.php" && echo VIEWS_OK
grep -q sample-reports "$PLUGIN/routes/web.php" && echo ROUTES_OK
curl -sS "http://127.0.0.1/api/trust-verification/sample-reports/public?type=tenant" -H "Host: admin-homes.sukoon.group" | head -c 240
echo
echo TASK13_DEPLOY_OK

#!/usr/bin/env bash
set -euo pipefail
ADMIN=/www/wwwroot/admin-homes
HOMES=/www/wwwroot/homes.sukoon.group

cd /tmp
tar -xzf tv-myorders-fix-plugin.tgz
rsync -a TrustVerification/ "$ADMIN/app/Plugins/TrustVerification/"
chown -R www:www "$ADMIN/app/Plugins/TrustVerification"

tar -xzf tv-myorders-fix-frontend.tgz -C /tmp
rsync -a /tmp/trust-verification/ "$HOMES/src/plugins/trust-verification/"
cp /tmp/pages-my-verification-orders.jsx "$HOMES/pages/my-verification-orders/index.jsx"
chown -R www:www "$HOMES/src/plugins/trust-verification" "$HOMES/pages/my-verification-orders/index.jsx"

cd "$ADMIN"
php artisan view:clear
php artisan route:clear

cd "$HOMES"
npm run build
pm2 restart homes-sukoon
systemctl restart php-fpm-83

php web-fix/tv-qa-order-report.php TV-EYMTZLKB
echo MY_ORDERS_FIX_DEPLOY_OK

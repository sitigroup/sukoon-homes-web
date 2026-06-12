#!/usr/bin/env bash
set -euo pipefail
ADMIN=/www/wwwroot/admin-homes
HOMES=/www/wwwroot/homes.sukoon.group

cd /tmp
tar -xzf tv-13c-plugin.tgz
rsync -a TrustVerification/ "$ADMIN/app/Plugins/TrustVerification/"
chown -R www:www "$ADMIN/app/Plugins/TrustVerification"

tar -xzf tv-13c-fe.tgz -C /tmp
rsync -a /tmp/trust-verification/ "$HOMES/src/plugins/trust-verification/"
cp /tmp/pages-my-verification-orders.jsx "$HOMES/pages/my-verification-orders/index.jsx"
chown -R www:www "$HOMES/src/plugins/trust-verification" "$HOMES/pages/my-verification-orders/index.jsx"

cd "$ADMIN"
php artisan view:clear
php artisan route:clear
php artisan optimize:clear

cd "$HOMES"
npm run build
pm2 restart homes-sukoon

php "$ADMIN/web-fix/tv-qa-format-order-report.php" TV-EYMTZLKB 2>/dev/null | head -40 || true
echo TASK_13C_DATETIME_DEPLOY_OK

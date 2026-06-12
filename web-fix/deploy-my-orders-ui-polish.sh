#!/usr/bin/env bash
set -euo pipefail
HOMES=/www/wwwroot/homes.sukoon.group
cp /tmp/pages-my-verification-orders.jsx "$HOMES/pages/my-verification-orders/index.jsx"
rsync -a /tmp/trust-verification/ "$HOMES/src/plugins/trust-verification/"
chown -R www:www "$HOMES/pages/my-verification-orders/index.jsx" "$HOMES/src/plugins/trust-verification"
cd "$HOMES" && npm run build && pm2 restart homes-sukoon
echo MY_ORDERS_UI_POLISH_DEPLOY_OK

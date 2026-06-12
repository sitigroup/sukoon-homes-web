#!/usr/bin/env bash
set -euo pipefail
HOMES=/www/wwwroot/homes.sukoon.group

cp /tmp/UserSideBar.jsx "$HOMES/src/components/user/UserSideBar.jsx"
cp /tmp/pages-my-verification-orders.jsx "$HOMES/pages/my-verification-orders/index.jsx"
chown www:www "$HOMES/src/components/user/UserSideBar.jsx" "$HOMES/pages/my-verification-orders/index.jsx"

cd "$HOMES"
npm run build
pm2 restart homes-sukoon
echo USER_SIDEBAR_TV_ORDERS_DEPLOY_OK

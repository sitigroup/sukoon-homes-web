#!/usr/bin/env bash
# Deploy My Verification Orders — hub placement + account dropdown (frontend only)
set -euo pipefail
HOMES=/www/wwwroot/homes.sukoon.group

cd /tmp
tar -xzf tv-account-menu-fe.tgz -C /tmp
rsync -a /tmp/trust-verification/ "$HOMES/src/plugins/trust-verification/"
cp /tmp/pages-my-verification-orders.jsx "$HOMES/pages/my-verification-orders/index.jsx"
cp /tmp/UserDropDown.jsx "$HOMES/src/components/reusable-components/UserDropDown.jsx"
chown -R www:www "$HOMES/src/plugins/trust-verification" \
  "$HOMES/pages/my-verification-orders/index.jsx" \
  "$HOMES/src/components/reusable-components/UserDropDown.jsx"

cd "$HOMES"
npm run build
pm2 restart homes-sukoon
echo TV_ACCOUNT_MENU_ACCESS_DEPLOY_OK

#!/usr/bin/env bash
set -euo pipefail
HOMES="/www/wwwroot/homes.sukoon.group"
WF="/tmp/webfix-18/web-fix"
TV="$HOMES/src/plugins/trust-verification"
mkdir -p "$TV/ui"
cp "$WF/plugins/trust-verification/trustVerificationApi.js" "$TV/"
cp "$WF/plugins/trust-verification/ui/"*.jsx "$TV/ui/"
cp "$WF/plugins/trust-verification/ui/"*.js "$TV/ui/"
cp "$WF/plugins/trust-verification/ui/trustVerificationPremium.module.css" "$TV/ui/"
cp "$WF/homes-frontend/src/components/owner-details-card/OwnerDetailsCard.jsx" \
  "$HOMES/src/components/owner-details-card/OwnerDetailsCard.jsx"
chown -R www:www "$TV" "$HOMES/src/components/owner-details-card"
cd "$HOMES"
export NODE_OPTIONS="${NODE_OPTIONS:---max-old-space-size=4096}"
npm run build
pm2 restart homes-sukoon 2>/dev/null || pm2 restart all 2>/dev/null || true
echo FRONTEND_18B_DONE

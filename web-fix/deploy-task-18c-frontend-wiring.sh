#!/usr/bin/env bash
# Task 18C — deploy tenant reliability frontend wiring to homes
set -euo pipefail

HOMES="/www/wwwroot/homes.sukoon.group"
WEB_FIX="${WEB_FIX:-/root/cursr/web-fix}"

TV="$HOMES/src/plugins/trust-verification"
mkdir -p "$TV/ui"

cp "$WEB_FIX/plugins/trust-verification/ui/"*.jsx "$TV/ui/"
cp "$WEB_FIX/plugins/trust-verification/ui/"*.js "$TV/ui/"
cp "$WEB_FIX/plugins/trust-verification/ui/trustVerificationPremium.module.css" "$TV/ui/"

COMPONENTS=(
  "user/UserInterested.jsx"
  "agent/AgentChat.jsx"
  "agent/appointment/AppointmentDetailsCard.jsx"
  "owner-details-card/OwnerDetailsCard.jsx"
)

for rel in "${COMPONENTS[@]}"; do
  src="$WEB_FIX/homes-frontend/src/components/$rel"
  if [[ -f "$src" ]]; then
    cp "$src" "$HOMES/src/components/$rel"
    echo "OK $rel"
  else
    echo "SKIP missing $src"
  fi
done

chown -R www:www "$TV" "$HOMES/src/components/user" "$HOMES/src/components/agent" "$HOMES/src/components/owner-details-card"
cd "$HOMES"
export NODE_OPTIONS="${NODE_OPTIONS:---max-old-space-size=4096}"
npm run build
pm2 restart homes-sukoon 2>/dev/null || pm2 restart all 2>/dev/null || true
echo "Task 18C frontend wiring deploy finished."

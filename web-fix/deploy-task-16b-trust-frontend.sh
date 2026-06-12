#!/usr/bin/env bash
# Task 16B — deploy trust score frontend to homes.sukoon.group
set -euo pipefail

HOMES="/www/wwwroot/homes.sukoon.group"
REPO="${REPO:-/root/cursr}"
WEB_FIX="${WEB_FIX:-$REPO/web-fix}"

if [[ ! -d "$WEB_FIX" ]]; then
  echo "Set REPO or copy web-fix to server. WEB_FIX=$WEB_FIX not found."
  exit 1
fi

TV="$HOMES/src/plugins/trust-verification"
mkdir -p "$TV/ui"

cp "$WEB_FIX/plugins/trust-verification/trustVerificationApi.js" "$TV/"
cp "$WEB_FIX/plugins/trust-verification/TrustProfileSection.jsx" "$TV/"
cp "$WEB_FIX/plugins/trust-verification/ui/"*.jsx "$TV/ui/"
cp "$WEB_FIX/plugins/trust-verification/ui/"*.js "$TV/ui/" 2>/dev/null || true
cp "$WEB_FIX/plugins/trust-verification/ui/trustVerificationPremium.module.css" "$TV/ui/"

COMPONENTS=(
  "cards/PropertyVerticalCard.jsx"
  "cards/PropertyHorizontalCard.jsx"
  "cards/AgentHorizontalCard.jsx"
  "owner-details-card/OwnerDetailsCard.jsx"
  "user/UserProfile.jsx"
  "agent/AgentProfile.jsx"
  "dashboard/ProfileCard.jsx"
  "reusable-components/UserDropDown.jsx"
)

for rel in "${COMPONENTS[@]}"; do
  src="$WEB_FIX/homes-frontend/src/components/$rel"
  if [[ "$rel" == "reusable-components/UserDropDown.jsx" ]]; then
    src="$WEB_FIX/UserDropDown.jsx"
  fi
  if [[ -f "$src" ]]; then
    cp "$src" "$HOMES/src/components/$rel"
    echo "OK $rel"
  else
    echo "SKIP missing $src"
  fi
done

chown -R www:www "$TV" "$HOMES/src/components/cards" "$HOMES/src/components/owner-details-card" \
  "$HOMES/src/components/user" "$HOMES/src/components/agent" "$HOMES/src/components/dashboard" \
  "$HOMES/src/components/reusable-components" 2>/dev/null || true

cd "$HOMES"
export NODE_OPTIONS="${NODE_OPTIONS:---max-old-space-size=4096}"
npm run build
pm2 restart homes-sukoon 2>/dev/null || pm2 restart all 2>/dev/null || true

echo "Task 16B frontend deploy finished."

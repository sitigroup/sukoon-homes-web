#!/usr/bin/env bash
# Post-deploy verification for Sukoon Theme plugin (Step 9)
set -euo pipefail

ADMIN_URL="${HEALTH_BASE_URL:-https://admin-homes.sukoon.group}"
HOMES_URL="${HOMES_URL:-https://homes.sukoon.group}"
PM2_APP="${PM2_APP:-homes-sukoon}"
FAIL=0

check() {
  local name="$1"
  local url="$2"
  local expect="${3:-200}"
  local code
  code="$(curl -sSL -o /dev/null -w '%{http_code}' "$url" || echo "000")"
  if [[ "$code" == "$expect" ]]; then
    echo "PASS  $name ($code) $url"
  else
    echo "FAIL  $name (got $code, want $expect) $url" >&2
    FAIL=1
  fi
}

echo "==> API"
check "theme public API" "$ADMIN_URL/api/theme/public"
check "web-settings (no break)" "$ADMIN_URL/api/web-settings"
check "trust-verification packages" "$ADMIN_URL/api/trust-verification/packages"

echo ""
echo "==> Frontend pages"
check "home" "$HOMES_URL/"
check "property listing" "$HOMES_URL/properties"
check "verification hub" "$HOMES_URL/verification"
check "my verification orders" "$HOMES_URL/my-verification-orders"

echo ""
echo "==> PM2"
if pm2 describe "$PM2_APP" >/dev/null 2>&1; then
  status="$(pm2 jlist 2>/dev/null | grep -o "\"name\":\"$PM2_APP\"[^}]*\"status\":\"[^\"]*\"" | head -1 || true)"
  if echo "$status" | grep -q '"status":"online"'; then
    echo "PASS  pm2 $PM2_APP online"
  else
    echo "WARN  pm2 $PM2_APP — check: pm2 describe $PM2_APP" >&2
  fi
else
  echo "WARN  pm2 app $PM2_APP not found" >&2
fi

echo ""
if [[ "$FAIL" -eq 0 ]]; then
  echo "VERIFY LIVE: all automated checks passed."
  echo "Manual: property details, profile, Trust Verification payment flow, CMS pages."
else
  echo "VERIFY LIVE: FAILURES — consider rollback: bash web-fix/rollback-theme-plugin.sh" >&2
  exit 1
fi

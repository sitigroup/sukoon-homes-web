#!/usr/bin/env bash
# Post-deploy health check for Laravel plugins on admin-homes.
# Usage: plugin-health-check.sh [PLUGIN_NAME]
#   PLUGIN_NAME defaults to TrustVerification
#
# Exit 0 = all checks passed. Exit 1 = one or more failures.

set -euo pipefail

PLUGIN_NAME="${1:-TrustVerification}"
ADMIN="${ADMIN_ROOT:-/www/wwwroot/admin-homes}"
WEB_USER="${WEB_USER:-www}"
WEB_GROUP="${WEB_GROUP:-www}"
BASE_URL="${HEALTH_BASE_URL:-https://admin-homes.sukoon.group}"
API_PATH="${HEALTH_API_PATH:-/api/web-settings}"
LOG_FILE="$ADMIN/storage/logs/laravel.log"
PLUGIN_DIR="$ADMIN/app/Plugins/$PLUGIN_NAME"
SP_FILE="$PLUGIN_DIR/${PLUGIN_NAME}ServiceProvider.php"

ERRORS=0

fail() {
  echo "FAIL: $*"
  ERRORS=$((ERRORS + 1))
}

pass() {
  echo "OK:   $*"
}

warn() {
  echo "WARN: $*"
}

echo "==> Plugin health check: $PLUGIN_NAME"
echo "    Admin:  $ADMIN"
echo "    Plugin: $PLUGIN_DIR"
echo ""

# --- Plugin directory exists ---
if [[ ! -d "$PLUGIN_DIR" ]]; then
  fail "Plugin directory missing: $PLUGIN_DIR"
  echo ""
  echo "HEALTH CHECK FAILED ($ERRORS issue(s))"
  exit 1
fi

# --- Service provider readable by www ---
if [[ ! -f "$SP_FILE" ]]; then
  fail "Service provider file missing: $SP_FILE"
elif sudo -u "$WEB_USER" test -r "$SP_FILE" 2>/dev/null; then
  pass "Service provider readable by $WEB_USER"
else
  fail "Service provider NOT readable by $WEB_USER ($SP_FILE)"
fi

# --- Plugin ownership (sample up to 5 bad paths) ---
BAD_OWNERS=$(find "$PLUGIN_DIR" \( ! -user "$WEB_USER" -o ! -group "$WEB_GROUP" \) 2>/dev/null | head -5 || true)
if [[ -z "$BAD_OWNERS" ]]; then
  pass "Plugin owned by $WEB_USER:$WEB_GROUP"
else
  fail "Plugin has wrong ownership (examples: $(echo "$BAD_OWNERS" | tr '\n' ' '))"
fi

# --- Plugin permissions: no mode 700 dirs ---
RESTRICTED=$(find "$PLUGIN_DIR" -type d ! -perm -o+x 2>/dev/null | head -3 || true)
if [[ -z "$RESTRICTED" ]]; then
  pass "Plugin directories world/others traversable (no 700-only dirs)"
else
  fail "Plugin has restricted directories (www cannot traverse): $(echo "$RESTRICTED" | tr '\n' ' ')"
fi

# --- storage writable ---
STORAGE_OK=1
for path in "$ADMIN/storage" "$ADMIN/storage/logs" "$ADMIN/storage/framework" "$ADMIN/bootstrap/cache"; do
  if [[ -e "$path" ]] && ! sudo -u "$WEB_USER" test -w "$path" 2>/dev/null; then
    fail "Not writable by $WEB_USER: $path"
    STORAGE_OK=0
  fi
done
if [[ "$STORAGE_OK" -eq 1 ]]; then
  pass "storage and bootstrap/cache writable by $WEB_USER"
fi

# --- API web-settings returns 200 ---
API_URL="${BASE_URL%/}${API_PATH}"
HTTP_CODE=$(curl -sS -o /dev/null -w "%{http_code}" --connect-timeout 15 --max-time 30 "$API_URL" 2>/dev/null || echo "000")
if [[ "$HTTP_CODE" == "200" ]]; then
  pass "GET $API_URL -> 200"
else
  fail "GET $API_URL -> $HTTP_CODE (expected 200)"
fi

# --- Trust Verification routes (when that plugin) ---
if [[ "$PLUGIN_NAME" == "TrustVerification" ]]; then
  ROUTE_OUT=$(cd "$ADMIN" && sudo -u "$WEB_USER" php artisan route:list --path=trust-verification 2>/dev/null || true)
  if echo "$ROUTE_OUT" | grep -q "trust-verification"; then
    pass "Trust Verification routes load"
  else
    fail "Trust Verification routes missing (php artisan route:list --path=trust-verification)"
  fi
fi

# --- Recent deploy-related errors in laravel.log ---
if [[ -f "$LOG_FILE" ]]; then
  TODAY=$(date +%Y-%m-%d)
  RECENT=$(grep -a "production.ERROR" "$LOG_FILE" 2>/dev/null | grep "$TODAY" | tail -3 || true)
  DEPLOY_ERRORS=$(echo "$RECENT" | grep -iE "Permission denied|${PLUGIN_NAME}ServiceProvider|Class .* not found" || true)
  if [[ -n "$DEPLOY_ERRORS" ]]; then
    fail "Recent production.ERROR today (deploy-related):"
    echo "$DEPLOY_ERRORS" | while IFS= read -r line; do
      echo "       $(echo "$line" | cut -c1-160)"
    done
  elif [[ -n "$RECENT" ]]; then
    warn "production.ERROR entries exist today but none look plugin-permission related"
  else
    pass "No production.ERROR entries today in laravel.log"
  fi
else
  warn "laravel.log not found at $LOG_FILE"
fi

echo ""
if [[ "$ERRORS" -gt 0 ]]; then
  echo "HEALTH CHECK FAILED ($ERRORS issue(s))"
  exit 1
fi

echo "HEALTH CHECK PASSED"
exit 0

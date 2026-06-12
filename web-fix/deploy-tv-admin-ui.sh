#!/usr/bin/env bash
# Deploy Trust Verification admin UI (all views). Run from repo root on server or via scp+ssh.
set -euo pipefail

ADMIN="${ADMIN_ROOT:-/www/wwwroot/admin-homes}"
PLUGIN="$ADMIN/app/Plugins/TrustVerification"
REPO="${REPO:-/root/cursr}"
SRC="$REPO/plugins/TrustVerification/views/admin"

echo "==> Sync admin views"
rsync -a "$SRC/" "$PLUGIN/views/admin/"

if [[ -f "$REPO/plugins/TrustVerification/Services/TrustVerificationService.php" ]]; then
  cp "$REPO/plugins/TrustVerification/Services/TrustVerificationService.php" "$PLUGIN/Services/"
fi

echo "==> Permissions"
chown -R www:www "$PLUGIN"
find "$PLUGIN" -type d -exec chmod 755 {} \;
find "$PLUGIN" -type f -exec chmod 644 {} \;

echo "==> Clear caches (never config:cache)"
cd "$ADMIN"
sudo -u www php artisan optimize:clear
sudo -u www php artisan view:clear

echo "==> Health"
curl -sS -o /dev/null -w "web-settings: %{http_code}\n" "${HEALTH_BASE_URL:-https://admin-homes.sukoon.group}/api/web-settings"

echo "DEPLOY_OK — hard-refresh browser (Ctrl+Shift+R) on all TV admin pages"

#!/bin/bash
# Deploy Trust Verification v1 — backup first, plugin-only, no silent core edits
set -euo pipefail

REPO="${REPO:-$(cd "$(dirname "$0")/.." && pwd)}"
ADMIN="/www/wwwroot/admin-homes"
WEB="/www/wwwroot/homes.sukoon.group"

echo "==> STEP 0: Fresh backup (required)"
bash "$REPO/scripts/final-sukoon-complete-backup.sh"

echo "==> STEP 1: Admin plugin (no core)"
cd "$ADMIN"
SKIP_BACKUP_CHECK=1 php "$REPO/web-fix/install-trust-verification.php"
chown -R www:www "$ADMIN/app/Plugins/TrustVerification"
find "$ADMIN/app/Plugins/TrustVerification" -type d -exec chmod 755 {} \;
find "$ADMIN/app/Plugins/TrustVerification" -type f -exec chmod 644 {} \;

echo "==> STEP 2: Optional provider registration (skipped by default)"
echo "    If routes 404, run manually after approval:"
echo "    php $REPO/web-fix/patch-trust-verification-register.php"

echo "==> STEP 3: Web plugin + pages"
SKIP_BACKUP_CHECK=1 php "$REPO/web-fix/install-trust-verification-web.php"
pm2 restart homes-sukoon || true

echo "Done."
echo "Admin queue: https://admin-homes.sukoon.group/trust-verification"
echo "Web hub:     https://homes.sukoon.group/verification"
echo "Tenant:      https://homes.sukoon.group/tenant-verification-in-barmer"
echo "Owner:       https://homes.sukoon.group/owner-verification-in-barmer"

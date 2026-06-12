#!/usr/bin/env bash
set -euo pipefail
ADMIN=/www/wwwroot/admin-homes
HOMES=/www/wwwroot/homes.sukoon.group
FIX="$(cd "$(dirname "$0")" && pwd)"

echo "=== Admin: Cashfree return_url + TV payment service + fallback blade ==="
cp "$FIX/CashfreePayment.php" "$ADMIN/app/Services/Payment/CashfreePayment.php"
cp "$FIX/../plugins/TrustVerification/Services/TrustVerificationPaymentService.php" \
  "$ADMIN/app/Plugins/TrustVerification/Services/TrustVerificationPaymentService.php"
cp "$FIX/paystack.blade.php" "$ADMIN/resources/views/payments/responses/paystack.blade.php"

if ! grep -q '^WEB_URL=' "$ADMIN/.env" 2>/dev/null; then
  echo 'WEB_URL="https://homes.sukoon.group"' >> "$ADMIN/.env"
  echo "Added WEB_URL to admin .env"
fi

cd "$ADMIN" && php artisan config:clear && php artisan route:clear

echo "=== Web: payment return page + hook ==="
mkdir -p "$HOMES/pages/payment/trust-verification-complete"
cp "$FIX/pages-trust-verification-payment-return.jsx" \
  "$HOMES/pages/payment/trust-verification-complete/index.jsx"
cp -r "$FIX/plugins/trust-verification/"* "$HOMES/src/plugins/trust-verification/"

cd "$HOMES" && npm run build
pm2 restart homes-sukoon 2>/dev/null || true

echo "=== Done ==="

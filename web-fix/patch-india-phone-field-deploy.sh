#!/usr/bin/env bash
set -euo pipefail
HOMES=/www/wwwroot/homes.sukoon.group
FIX="$(cd "$(dirname "$0")" && pwd)"

cp "$FIX/phoneFieldUtils.js" "$HOMES/src/utils/phoneFieldUtils.js"
cp "$FIX/IndiaPhoneInput.jsx" "$HOMES/src/components/forms/IndiaPhoneInput.jsx"
cp "$FIX/PhoneLoginForm.jsx" "$HOMES/src/components/forms/PhoneLoginForm.jsx"
cp "$FIX/RegisterForm.jsx" "$HOMES/src/components/forms/RegisterForm.jsx"
cp "$FIX/LoginModal.jsx" "$HOMES/src/components/modal/LoginModal.jsx"

cd "$HOMES" && npm run build
pm2 restart homes-sukoon 2>/dev/null || true
echo "India phone field deployed."

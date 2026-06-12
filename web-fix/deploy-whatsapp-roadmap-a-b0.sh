#!/usr/bin/env bash
# Deploy WhatsApp Phase A (preview + delivery) + Phase B0 inbox UI
set -euo pipefail

REMOTE="${REMOTE:-srv1534644}"
ROOT="${ROOT:-/www/wwwroot/admin-homes}"
PLUGIN="$ROOT/app/Plugins/Whatsapp"
SRC="$(cd "$(dirname "$0")/plugins/Whatsapp/src" && pwd)"

echo "==> Sync plugin from $SRC"
rsync -av --delete \
  --exclude '.git' \
  "$SRC/" "$REMOTE:$PLUGIN/"

echo "==> Clear caches"
ssh "$REMOTE" "cd $ROOT && sudo -u www php artisan optimize:clear"

echo "==> Done. Verify:"
echo "  - $ROOT/whatsapp/inbox"
echo "  - $ROOT/whatsapp/delivery"

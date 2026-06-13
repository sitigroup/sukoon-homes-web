#!/usr/bin/env bash
# Phase C — richer /rent/ pages + leads + GSC/GA4 (plugin + Next.js)
set -euo pipefail
HOST="${DEPLOY_HOST:-srv1534644-ipv4}"
ADMIN="${ADMIN_ROOT:-/www/wwwroot/admin-homes}"
WEB="${WEB_ROOT:-/www/wwwroot/homes.sukoon.group}"
PLUGIN="$(cd "$(dirname "$0")/../../plugins/SeoEngine/src" && pwd)"
WA="$(cd "$(dirname "$0")/../../plugins/Whatsapp/src/Support" && pwd)"
C1="$(cd "$(dirname "$0")" && pwd)"

echo "==> Sync SeoEngine plugin (Phase C)"
ssh -o ConnectTimeout=25 "$HOST" "mkdir -p '$ADMIN/app/Plugins/SeoEngine'"
cd "$PLUGIN" && tar -cf - . | ssh -o ConnectTimeout=25 "$HOST" "cd '$ADMIN/app/Plugins/SeoEngine' && tar -xf -"

echo "==> WhatsApp event catalog (seo_engine_lead events)"
scp -o ConnectTimeout=25 "$WA/WhatsappEventCatalog.php" "$HOST:$ADMIN/app/Plugins/Whatsapp/Support/WhatsappEventCatalog.php"

echo "==> Sync Next.js Phase C rent files"
ssh -o ConnectTimeout=25 "$HOST" "mkdir -p '$WEB/src/plugins/seo-engine' '$WEB/pages/rent'"
scp -o ConnectTimeout=25 "$C1/RentPageView.jsx" "$HOST:$WEB/src/plugins/seo-engine/RentPageView.jsx"
scp -o ConnectTimeout=25 "$C1/RentPageComponents.jsx" "$HOST:$WEB/src/plugins/seo-engine/RentPageComponents.jsx"
scp -o ConnectTimeout=25 "$C1/rentLayout.js" "$HOST:$WEB/src/plugins/seo-engine/rentLayout.js"
scp -o ConnectTimeout=25 "$C1/rentLeadApi.js" "$HOST:$WEB/src/plugins/seo-engine/rentLeadApi.js"
scp -o ConnectTimeout=25 "$C1/RentGa4Tracker.jsx" "$HOST:$WEB/src/plugins/seo-engine/RentGa4Tracker.jsx"
scp -o ConnectTimeout=25 "$C1/rent-[[...segments]].jsx" "$HOST:$WEB/pages/rent/[[...segments]].jsx"

echo "==> Migrate + clear cache + build"
ssh -o ConnectTimeout=25 "$HOST" bash -s <<REMOTE
set -euo pipefail
chown -R www:www '$ADMIN/app/Plugins/SeoEngine'
cd '$ADMIN'
php artisan migrate --path=app/Plugins/SeoEngine/database/migrations/2026_06_14_000001_create_seo_engine_leads_table.php --force
php artisan optimize:clear
cd '$WEB'
npm run build
pm2 restart homes-sukoon 2>/dev/null || pm2 restart all
REMOTE

echo "DONE Phase C deploy"

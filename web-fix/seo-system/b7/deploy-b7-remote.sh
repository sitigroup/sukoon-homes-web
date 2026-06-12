#!/usr/bin/env bash
# TASK B7 — Q&A Hub + monitoring (plugin + web guides route)
set -euo pipefail
HOST="${DEPLOY_HOST:-srv1534644-ipv4}"
ADMIN="${ADMIN_ROOT:-/www/wwwroot/admin-homes}"
WEB="${WEB_ROOT:-/www/wwwroot/homes.sukoon.group}"
PLUGIN="$(cd "$(dirname "$0")/../../plugins/SeoEngine/src" && pwd)"
B7="$(cd "$(dirname "$0")" && pwd)"

echo "==> Sync SeoEngine plugin (B7)"
ssh -o ConnectTimeout=25 "$HOST" "mkdir -p '$ADMIN/app/Plugins/SeoEngine'"
cd "$PLUGIN" && tar -cf - . | ssh -o ConnectTimeout=25 "$HOST" "cd '$ADMIN/app/Plugins/SeoEngine' && tar -xf -"

echo "==> Sync web guides route + plugin JS"
ssh -o ConnectTimeout=25 "$HOST" "mkdir -p '$WEB/pages/guides/[category]/[slug]' '$WEB/src/plugins/seo-engine'"
scp -o ConnectTimeout=25 "$B7/guides-[category]-[slug].jsx" "$HOST:$WEB/pages/guides/[category]/[slug]/index.jsx"
scp -o ConnectTimeout=25 "$B7/GuidePageView.jsx" "$HOST:$WEB/src/plugins/seo-engine/GuidePageView.jsx"
scp -o ConnectTimeout=25 "$B7/guidePageApi.js" "$HOST:$WEB/src/plugins/seo-engine/guidePageApi.js"
scp -o ConnectTimeout=25 "$B7/jsonld-guide.js" "$HOST:$WEB/src/plugins/seo-engine/jsonld-guide.js"
scp -o ConnectTimeout=25 "$(dirname "$B7")/a2/sitemap-generator.js" "$HOST:$WEB/scripts/sitemap-generator.js"

ssh -o ConnectTimeout=25 "$HOST" "chown -R www:www '$ADMIN/app/Plugins/SeoEngine' && cd '$ADMIN' && php artisan optimize:clear && php artisan seo-engine:seed-qa && php artisan seo-engine:build-sitemaps && php artisan seo-engine:digest-404"

echo "DONE B7 deploy (run npm run build + pm2 restart on web if guides route is new)"

#!/usr/bin/env bash
# Run on server after copying web-fix trust-verification files.
set -euo pipefail
HOMES_ROOT="${HOMES_ROOT:-/www/wwwroot/homes.sukoon.group}"
REPO_FIX="${REPO_FIX:-$(dirname "$(dirname "$0")")}"

echo "== Timeline unit tests =="
node "${REPO_FIX}/scripts/qa-timeline-test.mjs"

echo "== Next.js production build =="
cd "$HOMES_ROOT"
npm run build

echo "== Route smoke (expect 200; demo 200 after deploy) =="
for path in verification tenant-verification-in-barmer owner-verification-in-barmer my-verification-orders trust-verification-ui-demo; do
  code=$(curl -sL -o /dev/null -w "%{http_code}" "https://homes.sukoon.group/${path}/")
  echo "${code} /${path}/"
done

echo "OK — restart: pm2 restart homes-sukoon"

#!/bin/bash
# Safe deploy: build Next.js only while app is stopped, then restart PM2.
set -eu

HOMES_ROOT="${HOMES_ROOT:-/www/wwwroot/homes.sukoon.group}"
APP_NAME="${APP_NAME:-homes-sukoon}"

cd "$HOMES_ROOT"

echo "Stopping ${APP_NAME}..."
pm2 stop "$APP_NAME" || true

echo "Building..."
npm run build

echo "Starting ${APP_NAME}..."
pm2 start "$APP_NAME" || pm2 restart "$APP_NAME"

pm2 status "$APP_NAME"
echo "Deploy complete."

#!/usr/bin/env bash
set -euo pipefail
HOMES="/www/wwwroot/homes.sukoon.group"
WEB_FIX="/tmp/webfix-18c"
mkdir -p "$WEB_FIX"
tar -xzf /tmp/web-fix-task18c.tgz -C "$WEB_FIX"
ROOT="$WEB_FIX/web-fix"
[[ -d "$ROOT" ]] || ROOT="$WEB_FIX"
export WEB_FIX="$ROOT"
bash "$ROOT/deploy-task-18c-frontend-wiring.sh"

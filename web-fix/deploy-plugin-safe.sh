#!/usr/bin/env bash
# Safe Laravel plugin deploy — sync, fix permissions, clear cache, health check.
#
# Run ON the server after copying plugin files, or use --remote from a machine with SSH.
#
# Usage (on server):
#   bash web-fix/deploy-plugin-safe.sh TrustVerification /tmp/TrustVerification
#   bash web-fix/deploy-plugin-safe.sh TrustVerification   # permissions-only (already copied)
#
# Usage (from dev machine with repo + SSH):
#   bash web-fix/deploy-plugin-safe.sh TrustVerification --remote srv1534644 --source ./plugins/TrustVerification
#
# Options:
#   --remote HOST     SSH target (rsync source to /tmp, then run fix steps on server)
#   --source PATH     Local or remote source directory to rsync into plugin dir
#   --migrate         Run php artisan migrate --force after deploy (TrustVerification etc.)
#   --skip-health     Skip plugin-health-check.sh
#   --admin PATH      Admin root (default: /www/wwwroot/admin-homes)
#
# NEVER run: php artisan config:cache
#   constants.php uses define() — config:cache breaks the app.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ADMIN="${ADMIN_ROOT:-/www/wwwroot/admin-homes}"
WEB_USER="${WEB_USER:-www}"
WEB_GROUP="${WEB_GROUP:-www}"
BASE_URL="${HEALTH_BASE_URL:-https://admin-homes.sukoon.group}"
REMOTE=""
SOURCE=""
RUN_MIGRATE=0
SKIP_HEALTH=0
PLUGIN_NAME=""

usage() {
  cat <<EOF
Usage: $0 PLUGIN_NAME [SOURCE_DIR] [options]

Examples:
  $0 TrustVerification /tmp/TrustVerification
  $0 TrustVerification --remote srv1534644 --source ./plugins/TrustVerification
  $0 Theme /root/cursr/plugins/Theme --migrate

After every deploy: permissions fixed, optimize:clear, health check.
Do NOT run php artisan config:cache on this server.
EOF
  exit 1
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --remote)
      REMOTE="${2:?--remote requires HOST}"
      shift 2
      ;;
    --source)
      SOURCE="${2:?--source requires PATH}"
      shift 2
      ;;
    --migrate)
      RUN_MIGRATE=1
      shift
      ;;
    --skip-health)
      SKIP_HEALTH=1
      shift
      ;;
    --admin)
      ADMIN="${2:?--admin requires PATH}"
      shift 2
      ;;
    -h|--help)
      usage
      ;;
    *)
      if [[ -z "$PLUGIN_NAME" ]]; then
        PLUGIN_NAME="$1"
      elif [[ -z "$SOURCE" ]]; then
        SOURCE="$1"
      else
        echo "Unknown argument: $1" >&2
        usage
      fi
      shift
      ;;
  esac
done

[[ -n "$PLUGIN_NAME" ]] || usage

PLUGIN_DIR="$ADMIN/app/Plugins/$PLUGIN_NAME"
HEALTH_SCRIPT="$SCRIPT_DIR/plugin-health-check.sh"

run_on_server() {
  local remote_source="${1:-}"

  echo "==> Deploy plugin: $PLUGIN_NAME"
  echo "    Target: $PLUGIN_DIR"

  if [[ -n "$remote_source" ]]; then
    echo "==> Rsync $remote_source -> $PLUGIN_DIR"
    mkdir -p "$PLUGIN_DIR"
    rsync -a "${remote_source%/}/" "$PLUGIN_DIR/"
  elif [[ -n "$SOURCE" && -d "$SOURCE" ]]; then
    echo "==> Rsync $SOURCE -> $PLUGIN_DIR"
    mkdir -p "$PLUGIN_DIR"
    rsync -a "${SOURCE%/}/" "$PLUGIN_DIR/"
  fi

  [[ -d "$PLUGIN_DIR" ]] || {
    echo "ERROR: Plugin directory not found: $PLUGIN_DIR" >&2
    exit 1
  }

  echo "==> Fix ownership ($WEB_USER:$WEB_GROUP)"
  chown -R "$WEB_USER:$WEB_GROUP" "$PLUGIN_DIR"

  echo "==> Fix permissions (dirs 755, files 644)"
  find "$PLUGIN_DIR" -type d -exec chmod 755 {} \;
  find "$PLUGIN_DIR" -type f -exec chmod 644 {} \;

  if [[ -d "$ADMIN/bootstrap/cache" ]]; then
    echo "==> Fix bootstrap/cache ownership"
    chown -R "$WEB_USER:$WEB_GROUP" "$ADMIN/bootstrap/cache"
    find "$ADMIN/bootstrap/cache" -type f -name '*.php' -exec chmod 644 {} \; 2>/dev/null || true
  fi

  if [[ -d "$ADMIN/storage" ]]; then
    chown -R "$WEB_USER:$WEB_GROUP" "$ADMIN/storage" 2>/dev/null || true
  fi

  echo "==> Clear Laravel caches (optimize:clear only — never config:cache)"
  cd "$ADMIN"
  if id "$WEB_USER" &>/dev/null; then
    sudo -u "$WEB_USER" php artisan optimize:clear
  else
    php artisan optimize:clear
  fi

  if [[ "$RUN_MIGRATE" -eq 1 ]]; then
    echo "==> Run migrations"
    if id "$WEB_USER" &>/dev/null; then
      sudo -u "$WEB_USER" php artisan migrate --force
    else
      php artisan migrate --force
    fi
  fi

  if [[ "$SKIP_HEALTH" -eq 0 ]]; then
    echo "==> Health check"
    if [[ -f "$HEALTH_SCRIPT" ]]; then
      bash "$HEALTH_SCRIPT" "$PLUGIN_NAME"
    else
      echo "WARN: $HEALTH_SCRIPT not found — skipping health check"
      HTTP_CODE=$(curl -sS -o /dev/null -w "%{http_code}" "${BASE_URL%/}/api/web-settings" || echo "000")
      [[ "$HTTP_CODE" == "200" ]] || {
        echo "ERROR: /api/web-settings returned $HTTP_CODE" >&2
        exit 1
      }
      echo "OK: /api/web-settings -> 200"
    fi
  fi

  echo ""
  echo "=========================================="
  echo " DEPLOY SUCCESS: $PLUGIN_NAME"
  echo " Plugin path: $PLUGIN_DIR"
  echo " Verified:    ${BASE_URL%/}/api/web-settings"
  echo "=========================================="
}

if [[ -n "$REMOTE" ]]; then
  [[ -n "$SOURCE" ]] || {
    echo "ERROR: --remote requires --source PATH" >&2
    exit 1
  }
  [[ -d "$SOURCE" ]] || {
    echo "ERROR: --source not found: $SOURCE" >&2
    exit 1
  }

  REMOTE_TMP="/tmp/plugin-deploy-${PLUGIN_NAME}-$$"
  echo "==> Remote deploy to $REMOTE"
  echo "    Upload $SOURCE -> $REMOTE:$REMOTE_TMP"

  ssh "$REMOTE" "mkdir -p '$REMOTE_TMP'"
  rsync -a "${SOURCE%/}/" "${REMOTE}:${REMOTE_TMP}/"

  REMOTE_SCRIPT="/tmp/deploy-plugin-safe-$$.sh"
  scp "$0" "${REMOTE}:${REMOTE_SCRIPT}"
  scp "$HEALTH_SCRIPT" "${REMOTE}:/tmp/plugin-health-check.sh" 2>/dev/null || true

  MIGRATE_FLAG=""
  [[ "$RUN_MIGRATE" -eq 1 ]] && MIGRATE_FLAG="--migrate"
  SKIP_FLAG=""
  [[ "$SKIP_HEALTH" -eq 1 ]] && SKIP_FLAG="--skip-health"

  ssh "$REMOTE" "ADMIN_ROOT='$ADMIN' WEB_USER='$WEB_USER' WEB_GROUP='$WEB_GROUP' HEALTH_BASE_URL='$BASE_URL' bash '$REMOTE_SCRIPT' '$PLUGIN_NAME' '$REMOTE_TMP' $MIGRATE_FLAG $SKIP_FLAG; rm -rf '$REMOTE_TMP' '$REMOTE_SCRIPT'"

  exit 0
fi

run_on_server "$SOURCE"

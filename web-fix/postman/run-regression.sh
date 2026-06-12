#!/usr/bin/env bash
# Production-safe Trust Verification API run (Task 11B) — Safe Regression only
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

ENV_SLUG="${1:-production}"
case "$ENV_SLUG" in
  production|prod) ENV_FILE="Sukoon-Production.postman_environment.json" ;;
  local) ENV_FILE="Sukoon-Local.postman_environment.json" ;;
  *) echo "Usage: $0 [production|local]" >&2; exit 1 ;;
esac

if ! command -v newman >/dev/null 2>&1; then
  echo "Install Newman: npm install -g newman" >&2
  exit 1
fi

DELAY="${TV_REQUEST_DELAY_MS:-1500}"

EXTRA=(
  --env-var "run_destructive_tests=false"
  --delay-request "$DELAY"
)
if [[ -n "${TV_CUSTOMER_PASSWORD:-}" ]]; then
  EXTRA+=(--env-var "customer_password=${TV_CUSTOMER_PASSWORD}")
fi

echo "Running Safe Regression only (run_destructive_tests=false, delay=${DELAY}ms)"
newman run "Sukoon-Trust-Verification.postman_collection.json" \
  -e "$ENV_FILE" \
  --folder "Safe Regression" \
  --working-dir "$SCRIPT_DIR" \
  "${EXTRA[@]}" \
  "${@:2}"

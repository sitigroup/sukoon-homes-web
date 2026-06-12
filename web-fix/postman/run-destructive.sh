#!/usr/bin/env bash
# Staging/local only — Destructive + Rate Limit + Webhook + Admin folders
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

ENV_FILE="${1:-Sukoon-Local.postman_environment.json}"
DELAY="${TV_REQUEST_DELAY_MS:-1500}"

if ! command -v newman >/dev/null 2>&1; then
  echo "Install Newman: npm install -g newman" >&2
  exit 1
fi

echo "WARNING: run_destructive_tests=true — do NOT use on production."
for FOLDER in "Destructive Tests" "Rate Limit Tests" "Webhook Manual Tests" "Admin Session Tests"; do
  newman run "Sukoon-Trust-Verification.postman_collection.json" \
    -e "$ENV_FILE" \
    --folder "$FOLDER" \
    --env-var "run_destructive_tests=true" \
    --delay-request "$DELAY" \
    --working-dir "$SCRIPT_DIR"
done

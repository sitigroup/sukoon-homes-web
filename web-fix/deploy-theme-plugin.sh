#!/usr/bin/env bash
# Deploy Sukoon Theme plugin — use deploy-theme-plugin-staged.sh for production
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
echo "For production, use staged deploy:"
echo "  bash web-fix/deploy-theme-plugin-staged.sh all-through-admin"
echo ""
exec bash "$SCRIPT_DIR/deploy-theme-plugin-staged.sh" "${1:-help}"

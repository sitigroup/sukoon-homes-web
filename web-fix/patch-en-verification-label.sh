#!/bin/bash
# Add verification menu label to en.json (idempotent)
set -euo pipefail
EN="${1:-/www/wwwroot/homes.sukoon.group/src/utils/en.json}"
if grep -q '"verificationServices"' "$EN"; then
  echo "verificationServices already in en.json"
  exit 0
fi
python3 <<'PY'
import json, sys
path = sys.argv[1]
with open(path, encoding='utf-8') as f:
    data = json.load(f)
data['verificationServices'] = 'Verification'
with open(path, 'w', encoding='utf-8') as f:
    json.dump(data, f, ensure_ascii=False, indent=4)
    f.write('\n')
print('Added verificationServices to en.json')
PY
"$EN"

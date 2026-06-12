#!/bin/bash
# Deprecated — use scripts/final-sukoon-complete-backup.sh for full Sukoon backup
set -e
BACKUP_DIR="/www/wwwroot/admin-homes/storage/backups/area-wise-hardened-complete-v2-20260520"
mkdir -p "$BACKUP_DIR/plugin"
cp -a /www/wwwroot/admin-homes/app/Plugins/AreaListing/. "$BACKUP_DIR/plugin/"
ENV_FILE="/www/wwwroot/admin-homes/.env"
source <(grep -E '^DB_(HOST|PORT|DATABASE|USERNAME|PASSWORD)=' "$ENV_FILE" | sed 's/^/export /')
SQL="$BACKUP_DIR/sql_admin_homes_full_20260520.sql"
mysqldump -h"${DB_HOST:-127.0.0.1}" -P"${DB_PORT:-3306}" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$SQL"
cat > "$BACKUP_DIR/MANIFEST.txt" <<EOF
Area Wise Hardened Complete v2 Backup
Created: $(date -u +"%Y-%m-%d %H:%M:%S UTC")
Host: admin-homes.sukoon.group

Contents:
- sql_admin_homes_full_20260520.sql (full database)
- plugin/ (AreaListing plugin snapshot)

Items included: 1 throttle, 2 project merge, 3 repair tool, 4 drift check, 5 similarity check, 6 API pagination, 7 archived age filter
EOF
(
  cd "$BACKUP_DIR"
  sha256sum sql_admin_homes_full_20260520.sql MANIFEST.txt > SHA256SUMS.txt
  find plugin -type f | sort | xargs sha256sum >> SHA256SUMS.txt
) 
ls -lah "$BACKUP_DIR"
echo "BACKUP_COMPLETE"
head -5 "$BACKUP_DIR/SHA256SUMS.txt"

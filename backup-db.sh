#!/bin/bash
set -e
BACKUP_DIR="/www/wwwroot/admin-homes/storage/backups/area-wise-hardened-items1-4-20260520"
ENV_FILE="/www/wwwroot/admin-homes/.env"
source <(grep -E '^DB_(HOST|PORT|DATABASE|USERNAME|PASSWORD)=' "$ENV_FILE" | sed 's/^/export /')
OUT="$BACKUP_DIR/sql_admin_homes_full_20260520.sql"
mysqldump -h"${DB_HOST:-127.0.0.1}" -P"${DB_PORT:-3306}" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$OUT"
ls -lh "$OUT"
echo DB_DUMP_OK

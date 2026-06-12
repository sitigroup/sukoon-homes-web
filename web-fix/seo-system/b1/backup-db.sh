#!/bin/bash
set -euo pipefail
cd /www/wwwroot/admin-homes
BACKUP="seo-engine-b1-$(date +%Y%m%d-%H%M%S).sql"
DB=$(grep '^DB_DATABASE=' .env | cut -d= -f2- | tr -d '"'"'"')
USER=$(grep '^DB_USERNAME=' .env | cut -d= -f2- | tr -d '"'"'"')
PASS=$(grep '^DB_PASSWORD=' .env | cut -d= -f2- | tr -d '"'"'"')
mkdir -p /www/backup
mysqldump -u"$USER" -p"$PASS" "$DB" > "/www/backup/$BACKUP"
ls -lh "/www/backup/$BACKUP"
echo "BACKUP_FILE=$BACKUP"

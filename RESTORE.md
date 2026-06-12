# Sukoon Homes — restore guide

Backups are created by `scripts/final-sukoon-complete-backup.sh` on the server.

## Backup location

- Folder: `/www/wwwroot/admin-homes/storage/backups/sukoon-final-complete-YYYYMMDD-HHMMSS/`
- Archive: same name with `.tar.gz`

## What is inside

| Path | Contents |
|------|----------|
| `admin/plugins/` | Full **AreaListing** and **NearbyPlaces** Laravel plugins |
| `admin/patches/` | Minimal patched wrteam files (API controllers, ProjectController, etc.) |
| `admin/public-js/` | `maps-helper.js`, property table CSS |
| `admin/views/property/` | Patched create / edit / index blades |
| `web/plugins/` | Next.js plugins: `area-listing`, `nearby-places`, `property-detail-switcher` |
| `web/patches/` | Minimal patched Next.js components |
| `db/full_database.sql` | Full MySQL dump |
| `db/area_listing_tables.sql` | Area listing tables only |
| `CHECKSUMS.sha256` | File integrity checksums |

## Restore plugins (safe — no wrteam conflict)

```bash
ADMIN=/www/wwwroot/admin-homes
BACKUP=/path/to/sukoon-final-complete-... 

cp -a "$BACKUP/admin/plugins/AreaListing" "$ADMIN/app/Plugins/"
cp -a "$BACKUP/admin/plugins/NearbyPlaces" "$ADMIN/app/Plugins/"

HOMES=/www/wwwroot/homes.sukoon.group
cp -a "$BACKUP/web/plugins/"* "$HOMES/src/plugins/"

cd "$ADMIN" && php artisan optimize:clear
cd "$HOMES" && npm run build && pm2 restart homes-sukoon
```

## Restore database (destructive — staging first)

```bash
mysql -u USER -p DATABASE < db/full_database.sql
```

## Restore core patches (after wrteam update)

Copy files from `admin/patches/` and `web/patches/` back to the same relative paths under `admin-homes` and `homes.sukoon.group`, then reconcile with any new wrteam files manually.

Local patch sources: `web-fix/` and `batch-a/` in the cursr repo.

## Verify

1. Admin: Area Listing + Nearby Places menus and property create/edit map
2. Web: homepage location search, categories count, property detail nearby section
3. Agent: appointment settings modals (no Radix console warnings)

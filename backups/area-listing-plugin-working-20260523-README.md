# Area Listing plugin — working backup (2026-05-23)

**Do not change** area/sub-area code unless explicitly requested. This archive matches production when area-wise flows were confirmed working.

## Archives

| Location | Path |
|----------|------|
| **Server** | `/www/wwwroot/backups/area-listing-plugin-working-20260523.tar.gz` |
| **Local** | `c:\Users\Desktop\cursr\backups\area-listing-plugin-working-20260523.tar.gz` |

**SHA-256:** `44007b7bc65f1aa210003035d94609d891e0b5948c0816b331f5115e7ccb76bc`

## Contents (46 files)

- `admin-homes/app/Plugins/AreaListing/` — Laravel plugin (controllers, models, services, admin views, routes)
- `admin-homes/database/migrations/` — `2026_05_14_*`, `2026_05_16_*`, `2026_05_17_*` area_listing migrations
- `frontend/area-listing/` — Next.js plugin (`AreaSubAreaSelector.jsx`, API, permissions, utils)
- `admin-homes/AppServiceProvider-AreaListing-refs.txt` — line refs for plugin registration

**Key file check:** `AreaListingService.php` ≈ 98,926 bytes (full class with `materializeLocationOnListingApproval`).

## Restore (server)

```bash
cd /www/wwwroot/backups
tar -xzf area-listing-plugin-working-20260523.tar.gz
cp -a area-listing-plugin-working-20260523/admin-homes/app/Plugins/AreaListing /www/wwwroot/admin-homes/app/Plugins/
cp -a area-listing-plugin-working-20260523/frontend/area-listing/* /www/wwwroot/homes.sukoon.group/src/plugins/area-listing/
cd /www/wwwroot/admin-homes && php artisan optimize:clear
cd /www/wwwroot/homes.sukoon.group && npm run build && pm2 restart homes-sukoon
```

## Not in this archive (integration only)

These touch area listing but live outside the plugin folder; frozen rules in `.cursor/rules/` still apply:

- `public/assets/js/maps-helper.js`
- Property/project admin blades (`listing-location-fields` include)
- `LocationComponent.jsx`, user/agent add-edit property/project pages

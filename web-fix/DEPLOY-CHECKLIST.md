# Deploy checklist — Sukoon admin + homes

Use this flow for **every** plugin or admin deploy. Skipping permission fix caused production **500** on all API routes (`web-settings`, `get-user-data`, etc.) when `TrustVerification` was rsync’d as `root` with mode `700`.

---

## Before deploy

- [ ] Run backup on server: `bash scripts/final-sukoon-complete-backup.sh`
- [ ] Confirm changes are in **plugin** or **documented patch** (not bulk core overwrite)
- [ ] Note plugin name exactly (e.g. `TrustVerification`, `Theme`, `AreaListing`)

---

## Required deploy flow (admin plugin)

### Option A — Safe deploy script (recommended)

**On server** (after scp/tar to a temp path):

```bash
cd /root/cursr   # or wherever web-fix lives
bash web-fix/deploy-plugin-safe.sh TrustVerification /tmp/TrustVerification
```

**From dev machine** (rsync + SSH in one step):

```bash
bash web-fix/deploy-plugin-safe.sh TrustVerification \
  --remote srv1534644 \
  --source ./plugins/TrustVerification
```

With migrations:

```bash
bash web-fix/deploy-plugin-safe.sh TrustVerification /tmp/TrustVerification --migrate
```

The script automatically:

1. `rsync` into `app/Plugins/PLUGIN_NAME/` (if source given)
2. `chown -R www:www`
3. `find … -type d chmod 755` / files `644`
4. Fix `bootstrap/cache` ownership if needed
5. `php artisan optimize:clear` **only**
6. Run `plugin-health-check.sh`

### Option B — Manual (same steps as script)

```bash
ADMIN=/www/wwwroot/admin-homes
PLUGIN=TrustVerification

rsync -a /tmp/TrustVerification/ "$ADMIN/app/Plugins/$PLUGIN/"
chown -R www:www "$ADMIN/app/Plugins/$PLUGIN"
find "$ADMIN/app/Plugins/$PLUGIN" -type d -exec chmod 755 {} \;
find "$ADMIN/app/Plugins/$PLUGIN" -type f -exec chmod 644 {} \;
chown -R www:www "$ADMIN/bootstrap/cache"

cd "$ADMIN"
sudo -u www php artisan optimize:clear
# migrations if needed:
sudo -u www php artisan migrate --force

bash /root/cursr/web-fix/plugin-health-check.sh "$PLUGIN"
```

---

## Health check only

```bash
bash web-fix/plugin-health-check.sh TrustVerification
```

Checks:

| Check | Pass criteria |
|-------|----------------|
| Service provider | `{Plugin}ServiceProvider.php` readable by `www` |
| Ownership | All plugin files `www:www` |
| Permissions | No `700` dirs blocking `www` |
| Storage | `storage/`, `bootstrap/cache` writable by `www` |
| API | `GET /api/web-settings` → **200** |
| TV routes | `route:list --path=trust-verification` (TrustVerification only) |
| Log | No today `Permission denied` / ServiceProvider errors |

Exit code **0** = safe. **1** = do not leave production; fix and re-run.

---

## Homes (Next.js) frontend deploy

After admin plugin deploy, if web files changed:

```bash
HOMES=/www/wwwroot/homes.sukoon.group
rsync -a web-fix/plugins/trust-verification/ "$HOMES/src/plugins/trust-verification/"
chown -R www:www "$HOMES/src/plugins/trust-verification"
cd "$HOMES" && npm run build && pm2 restart homes-sukoon
```

Then verify: `https://homes.sukoon.group/my-verification-orders`

---

## Never run on this server

```bash
# BREAKS PRODUCTION — constants.php uses define()
php artisan config:cache
```

**Use only:**

```bash
sudo -u www php artisan optimize:clear
```

Also avoid running `php artisan migrate` or `optimize:clear` as **root** without fixing ownership afterward.

---

## After deploy verification

```bash
curl -s -o /dev/null -w "web-settings: %{http_code}\n" \
  https://admin-homes.sukoon.group/api/web-settings

curl -s -o /dev/null -w "tv packages: %{http_code}\n" \
  "https://admin-homes.sukoon.group/api/trust-verification/packages?type=tenant&city=barmer"
```

Both should be **200**. Admin UI should load without console 500 on `web-settings` / `get-user-data`.

---

## Rollback

1. Restore plugin folder from backup under `storage/backups/`
2. `chown -R www:www app/Plugins/PLUGIN_NAME`
3. `sudo -u www php artisan optimize:clear`
4. `bash web-fix/plugin-health-check.sh PLUGIN_NAME`

---

## Scripts reference

| Script | Purpose |
|--------|---------|
| [`deploy-plugin-safe.sh`](deploy-plugin-safe.sh) | Sync + permissions + cache clear + health check |
| [`plugin-health-check.sh`](plugin-health-check.sh) | Post-deploy verification |
| [`scripts/final-sukoon-complete-backup.sh`](../scripts/final-sukoon-complete-backup.sh) | Pre-deploy backup |

---

## Root cause (May 2026 incident)

```
rsync as root → app/Plugins/TrustVerification mode 700 root:root
→ PHP user www cannot read TrustVerificationServiceProvider.php
→ Laravel bootstrap fails → ALL /api/* return 500
```

**Prevention:** Always run `deploy-plugin-safe.sh` (or manual chown/chmod steps) immediately after any plugin copy.

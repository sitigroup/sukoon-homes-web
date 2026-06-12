# Theme Plugin — Production deploy checklist

Run **on the server** after syncing `cursr` repo. Do not skip backup.

---

## Step 1 — Full backup

```bash
bash scripts/final-sukoon-complete-backup.sh
```

**Save** `BACKUP_DIR` and `ARCHIVE` from output.

Covers: admin-homes files, homes frontend patches, full database (`db/full_database.sql`).

---

## Step 2–3 — Deploy admin plugin + migrate (no publish)

```bash
bash web-fix/deploy-theme-plugin-staged.sh admin
```

- Copies `plugins/Theme/` → `app/Plugins/Theme/`
- Registers provider, sidebar, admin head inject
- Runs migrations + **draft-only** seeder
- **Does not** publish theme — live site unchanged

Or all-in-one through API verify:

```bash
bash web-fix/deploy-theme-plugin-staged.sh all-through-admin
```

---

## Step 4 — Verify API

```bash
bash web-fix/deploy-theme-plugin-staged.sh verify-api
```

Expected:

```json
{ "data": { "published": false, "tokens": null } }
```

---

## Step 5–6 — Admin manual test (before frontend)

1. Open **Appearance → Theme Settings**:  
   `https://admin-homes.sukoon.group/appearance/theme`
2. Click **Apply to draft** on **Sukoon Luxury Black**
3. Confirm **live preview** (property card mockup)
4. **Do not Publish** until frontend phase is deployed and you are ready for live theme

---

## Step 7–8 — Frontend build + PM2

After admin preset test on draft (and **Publish** when ready for live CSS):

```bash
bash web-fix/deploy-theme-plugin-staged.sh frontend
```

This runs:

- `rsync` → `src/plugins/theme/`
- Patches `_app.js` + `Layout.jsx`
- `npm run build`
- **`pm2 restart homes-sukoon`** — never `pm2 restart homes` unless confirmed

---

## Step 9 — Verify live

```bash
bash web-fix/deploy-theme-plugin-staged.sh verify-live
```

**Manual checks:**

| Area | URL / action |
|------|----------------|
| Home | `/` |
| Property listing | `/properties` |
| Property details | open any listing |
| Profile | `/user/profile` or account area |
| Trust Verification | `/verification`, `/my-verification-orders` |
| Payments | start TV order (do not complete if test) |
| CMS | legal / CMS frontend pages |

---

## Step 10 — Rollback if anything breaks

```bash
export BACKUP_DIR=/www/wwwroot/admin-homes/storage/backups/sukoon-final-complete-YYYYMMDD-HHMMSS
bash web-fix/rollback-theme-plugin.sh
```

Or restore full tarball from Step 1.

---

## Safety notes

| Rule | Detail |
|------|--------|
| No auto-publish | API returns `published: false` until admin clicks **Publish** |
| PM2 | Always `homes-sukoon` |
| Never | `php artisan config:cache` |
| Permissions | Plugin owned `www:www`, dirs 755, files 644 |

See also: [`MANIFEST-theme.md`](MANIFEST-theme.md)

# Sukoon Theme Plugin — MANIFEST

> **Policy:** Plugin-first. No bulk wrteam core edits. Minimal documented patches only.

## Backup first

```bash
bash scripts/final-sukoon-complete-backup.sh
```

Note backup folder name before deploy.

---

## Plugin structure

### Laravel (`plugins/Theme/` → `admin-homes/app/Plugins/Theme/`)

```
Theme/
├── ThemeServiceProvider.php
├── config/theme.php
├── Support/
│   ├── ThemePresets.php      # Sukoon Luxury Black, Modern White, Dark Premium
│   └── ThemeTokens.php       # Normalization + CSS variables
├── Services/
│   └── ThemeService.php      # Draft, publish, reset, version history
├── Models/
│   ├── ThemeSetting.php
│   └── ThemeVersion.php
├── Http/Controllers/
│   ├── Admin/ThemeAdminController.php
│   └── Api/ThemeApiController.php
├── routes/web.php            # Admin: /appearance/theme
├── routes/api.php            # Public: GET /api/theme/public
├── database/migrations/
├── database/seeders/ThemePresetSeeder.php
└── views/admin/
    ├── index.blade.php       # Live preview + controls
    └── partials/
        ├── theme-admin-styles.blade.php
        └── admin-theme-inject.blade.php
```

### Next.js (`web-fix/plugins/theme/` → `homes.sukoon.group/src/plugins/theme/`)

```
theme/
├── index.js
├── themeApi.js
├── themePresets.js
├── themeTokens.js
├── applyThemeVariables.js
├── SukoonThemeProvider.jsx
├── sukoonThemePremium.css
└── theme-preview.html        # Static preview (open in browser)
```

---

## Admin features

| Feature | Route / action |
|---------|----------------|
| Theme Settings UI | `GET /appearance/theme` |
| Save draft | `POST /appearance/theme/draft` |
| Publish | `POST /appearance/theme/publish` |
| Reset default | `POST /appearance/theme/reset` |
| Apply preset | `POST /appearance/theme/preset` |
| Restore version | `POST /appearance/theme/restore` |
| Public API | `GET /api/theme/public` |

**Permissions:** `web_settings` read/update (same as NearbyPlaces Web Settings).

**Theme controls:** primary, secondary, accent, sidebar, card, border, button, hover, link, muted, white, section, typography, border radius, shadow intensity.

**Presets:**

1. **Sukoon Luxury Black** — `#1F2937` / `#B89A4A` gold / `#F8F9FA` section
2. **Modern White** — clean white + blue accent
3. **Dark Premium** — charcoal + warm gold

---

## Deploy

```bash
bash web-fix/deploy-theme-plugin.sh
```

Or manual steps in `web-fix/deploy-theme-plugin.sh`.

### Core patches (optional, documented)

| Script | Purpose |
|--------|---------|
| `patch-theme-register.php` | Register `ThemeServiceProvider` in `config/app.php` |
| `patch-theme-sidebar.php` | Admin → Web Settings → Theme Settings |
| `patch-theme-admin-head.php` | Inject admin CSS variables in `layouts/main.blade.php` |
| `patch-theme-app.js` | Wrap `_app.js` with `SukoonThemeProvider` |
| `patch-theme-layout.js` | Re-apply theme after `getWebSetting()` in Layout.jsx |

---

## Changed / new files (this repo)

| Path | Role |
|------|------|
| `plugins/Theme/**` | Laravel plugin source |
| `web-fix/plugins/theme/**` | Next.js plugin source |
| `web-fix/patch-theme-*.php` | Admin registration + sidebar + head inject |
| `web-fix/patch-theme-*.js` | Next.js minimal hooks |
| `web-fix/deploy-theme-plugin.sh` | One-shot deploy |
| `web-fix/MANIFEST-theme.md` | This file |

**No wrteam core files modified in repo** — patches run on server only after backup + approval.

---

## Rollback steps

1. Restore from backup tarball created before deploy.
2. Or partial rollback:
   ```bash
   rm -rf /www/wwwroot/admin-homes/app/Plugins/Theme
   # Remove ThemeServiceProvider line from config/app.php
   # Revert sidebar + main.blade.php patches manually
   php artisan migrate:rollback --path=app/Plugins/Theme/database/migrations
   rm -rf /www/wwwroot/homes.sukoon.group/src/plugins/theme
   # Revert _app.js + Layout.jsx patches
   cd /www/wwwroot/homes.sukoon.group && npm run build && pm2 restart homes
   ```
3. Published theme cached 5 min — clear: `php artisan cache:clear`

---

## QA checklist

### Admin

- [ ] Sidebar shows **Theme Settings** under Web Settings (web_settings permission)
- [ ] `/appearance/theme` loads with 3 presets
- [ ] Color pickers update live preview instantly
- [ ] Save draft persists without publishing
- [ ] Publish updates frontend within cache TTL (~5 min) or hard refresh
- [ ] Reset default restores Sukoon Luxury Black
- [ ] Version history records publish / draft / reset / restore
- [ ] Restore version loads into draft

### Frontend (after `_app` + Layout patches)

- [ ] `GET /api/theme/public` returns tokens JSON
- [ ] Home, property cards, navbar, footer use `--sukoon-*` variables
- [ ] Property detail, search, filters, forms, pagination styled
- [ ] Profile / sidebar pages readable (no tiny fonts)
- [ ] Trust Verification pages still work (payment, CMS, orders)
- [ ] Mobile + tablet responsive

### Backend admin styling

- [ ] Trust Verification CMS readable
- [ ] Tables, buttons, filters use premium radius/shadows
- [ ] No permission regressions

### Non-regression

- [ ] Payments (Trust Verification Cashfree) unchanged
- [ ] CMS frontend/API unchanged
- [ ] SEO meta / SSR pages unchanged
- [ ] PM2 build succeeds
- [ ] Lighthouse performance within ±5% of baseline

---

## Screenshots

Open locally (no build required):

```
web-fix/plugins/theme/theme-preview.html
```

Admin UI screenshot: capture `/appearance/theme` after deploy on staging.

---

## Build result

Run on server after deploy:

```bash
cd /www/wwwroot/homes.sukoon.group && npm run build
```

Expected: build completes; `src/plugins/theme` included in Tailwind content if `patch-trust-verification-tailwind-content.js` already applied (covers all `src/plugins/**`).

---

## wrteam update survival

- All logic in `app/Plugins/Theme/` and `src/plugins/theme/`
- Re-run patch scripts after wrteam overwrites `config/app.php`, sidebar, `_app.js`, or `Layout.jsx`
- Document patch re-application in deploy notes

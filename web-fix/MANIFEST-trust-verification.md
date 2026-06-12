# Trust Verification — MANIFEST

> **Final report:** [`TRUST-VERIFICATION-FINAL-REPORT.md`](TRUST-VERIFICATION-FINAL-REPORT.md)  
> **Earlier status:** [`TRUST-VERIFICATION-STATUS-REPORT.md`](TRUST-VERIFICATION-STATUS-REPORT.md)  
> **Deploy steps:** [`TRUST-VERIFICATION-DEPLOY.md`](TRUST-VERIFICATION-DEPLOY.md)  
> Last updated: May 2026 (v2/v3, Barmer-only)

## Policy

- **Backup first:** `bash scripts/final-sukoon-complete-backup.sh` on server before deploy.
- **Plugin-first** — no bulk wrteam core edits. Custom code in plugin folders + documented minimal patches.
- **Route hooks:** `patch-trust-verification-routes.php` (preferred) or optional `patch-trust-verification-register.php`.

---

## Laravel plugin (copy entire folder)

| Source | Server |
|--------|--------|
| `plugins/TrustVerification/` | `/www/wwwroot/admin-homes/app/Plugins/TrustVerification/` |

Post-copy:

```bash
chown -R www:www app/Plugins/TrustVerification
find app/Plugins/TrustVerification -type d -exec chmod 755 {} \;
find app/Plugins/TrustVerification -type f -exec chmod 644 {} \;
cd /www/wwwroot/admin-homes
php artisan migrate --force --path=app/Plugins/TrustVerification/database/migrations
php artisan db:seed --class=App\\Plugins\\TrustVerification\\Database\\Seeders\\TvPackageSeeder --force
php artisan optimize:clear
```

---

## Web plugin

| Source | Server |
|--------|--------|
| `web-fix/plugins/trust-verification/` | `homes.sukoon.group/src/plugins/trust-verification/` |

Files: `TrustVerificationPage.jsx`, `VerifyOwnerPropertyCard.jsx`, `MyVerificationOrdersLink.jsx`, `trustVerificationApi.js`, `trustVerificationUtils.js`, `useTrustVerificationPayment.js`

**Premium UI (TASK 1 — UI only, no API/backend):**

| Path | Purpose |
|------|---------|
| `ui/VerificationLegalLinks.jsx` | Privacy / terms / refund links |
| `ui/trustVerificationLegalCopy.js` | Legal text constants (TASK 2) |
| `ui/TrustCard.jsx` … `VerificationSuccessScreen.jsx` | Reusable components (see `ui/index.js`) |
| `ui/VerificationHubPremium.jsx` | Hub hero, city selector, service cards, FAQ |
| `trust-verification-ui-preview.html` | Static local preview (open in browser, no build) |

| `web-fix/pages-trust-verification-ui-demo.jsx` | `pages/trust-verification-ui-demo/index.jsx` — component gallery |
| `web-fix/pages-verification-terms.jsx` | `pages/verification-terms/index.jsx` |
| `web-fix/pages-verification-privacy.jsx` | `pages/verification-privacy/index.jsx` |
| `web-fix/pages-verification-refund-policy.jsx` | `pages/verification-refund-policy/index.jsx` |

---

## Web pages (via `install-trust-verification-web.php` or manual copy)

| Source | Server |
|--------|--------|
| `web-fix/pages-verification-index.jsx` | `pages/verification/index.jsx` |
| `web-fix/pages-tenant-verification-in-barmer.jsx` | `pages/tenant-verification-in-barmer/index.jsx` |
| `web-fix/pages-owner-verification-in-barmer.jsx` | `pages/owner-verification-in-barmer/index.jsx` |
| `web-fix/pages-my-verification-orders.jsx` | `pages/my-verification-orders/index.jsx` |
| `web-fix/pages-tenant-verification-redirect.jsx` | `pages/tenant-verification/index.jsx` |
| `web-fix/pages-owner-verification-redirect.jsx` | `pages/owner-verification/index.jsx` |
| `web-fix/pages-tenant-verification-in-citySlug.jsx` | `pages/tenant-verification-in/[citySlug]/index.jsx` |
| `web-fix/pages-owner-verification-in-citySlug.jsx` | `pages/owner-verification-in/[citySlug]/index.jsx` |

| `web-fix/patch-trust-verification-next-rewrites.js` | `next.config.js` — maps `/tenant-verification-in-:city` → slash route |

`[citySlug]` pages use `getServerSideProps` (SSR). SEO hyphen URLs require the rewrites patch above.

---

## Core patches (manual — NOT in web installer)

Apply after wrteam web updates if overwritten:

| Source | Server (typical) |
|--------|------------------|
| `web-fix/Header.jsx` | `src/components/layout/Header.jsx` |
| `web-fix/PropertyDetails.jsx` | stock property detail component |
| `web-fix/plugins/property-detail-switcher/CustomPropertyDetailsPage.jsx` | custom layout property detail |

Translation label:

| Patch | Purpose |
|-------|---------|
| `web-fix/patch-en-verification-label.sh` | Adds `verificationServices` → `en.json` |

---

## Admin route / view hooks (minimal core)

| Patch | Files touched |
|-------|---------------|
| `web-fix/patch-trust-verification-routes.php` | `routes/api.php`, `routes/web.php`, `AppServiceProvider.php` |

Optional provider registration:

| Patch | File |
|-------|------|
| `web-fix/patch-trust-verification-register.php` | `config/app.php` |

---

## Deploy order

```bash
# 1. Backup (required)
bash scripts/final-sukoon-complete-backup.sh

# 2. Admin plugin
cd /www/wwwroot/admin-homes
SKIP_BACKUP_CHECK=1 php /path/to/cursr/web-fix/install-trust-verification.php
php /path/to/cursr/web-fix/patch-trust-verification-routes.php   # if not already applied
php /path/to/cursr/web-fix/migrate-trust-verification-reports.php   # dry-run; add --execute when ready
php artisan optimize:clear

# 3. Web plugin + pages
SKIP_BACKUP_CHECK=1 php /path/to/cursr/web-fix/install-trust-verification-web.php

# 4. Core patches (Header, PropertyDetails, CustomPropertyDetailsPage) — manual SCP/copy

# 5. Build + restart
cd /www/wwwroot/homes.sukoon.group
npm run build
pm2 restart homes-sukoon
```

Or: `bash scripts/deploy-trust-verification-v1.sh` then apply core patches + full page set if script is behind.

---

## Rollback

1. Remove `app/Plugins/TrustVerification/`
2. Remove web pages listed above + `src/plugins/trust-verification/`
3. Revert core patches if applied
4. `npm run build` + `pm2 restart homes-sukoon`
5. Restore from `storage/backups/sukoon-final-complete-*` if needed

---

## Docs in this folder

| File | Purpose |
|------|---------|
| `TRUST-VERIFICATION-STATUS-REPORT.md` | **Canonical** — built vs pending |
| `TRUST-VERIFICATION-DEPLOY.md` | Deploy commands + verify URLs |
| `TRUST-VERIFICATION-QA-CHECKLIST.md` | Manual E2E QA for ops |
| `migrate-trust-verification-reports.php` | Legacy PDF public → local migration |
| `MANIFEST-trust-verification.md` | This file — path mapping |

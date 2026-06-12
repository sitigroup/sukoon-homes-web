# Trust Verification — Deploy guide

> **Canonical status:** [`TRUST-VERIFICATION-STATUS-REPORT.md`](TRUST-VERIFICATION-STATUS-REPORT.md)  
> Last updated: May 2026 (v2/v3 deployed, Barmer-only)

## Rules (always)

1. **Fresh backup first** — `bash scripts/final-sukoon-complete-backup.sh` on the server.
2. **Plugin-only** — custom code in `app/Plugins/TrustVerification/` and `src/plugins/trust-verification/`. Minimal core hooks via `patch-trust-verification-routes.php` only.
3. **Do not rely on `install-trust-verification-web.php` alone** — it copies only 4 pages. See [Full web deploy](#full-web-deploy) below.

---

**404 on `/verification` or `/trust-verification` = not deployed yet.**

## Deploy (SSH) — minimum path

```bash
# 1) REQUIRED backup
bash /root/cursr/scripts/final-sukoon-complete-backup.sh

# 2) Admin — plugin copy + migrate + seed
cd /www/wwwroot/admin-homes
SKIP_BACKUP_CHECK=1 php /root/cursr/web-fix/install-trust-verification.php
chown -R www:www app/Plugins/TrustVerification
find app/Plugins/TrustVerification -type d -exec chmod 755 {} \;
find app/Plugins/TrustVerification -type f -exec chmod 644 {} \;

# 3) Route/view hooks (if not already applied)
php /root/cursr/web-fix/patch-trust-verification-routes.php
php artisan optimize:clear

# 4) Web — see Full web deploy below (installer alone is incomplete)
# 5) Rebuild + restart
cd /www/wwwroot/homes.sukoon.group
npm run build
pm2 restart homes-sukoon
```

## Full web deploy

The PHP installer copies plugin + 4 pages only. Production also needs:

| Staging | Server |
|---------|--------|
| `web-fix/plugins/trust-verification/` | `src/plugins/trust-verification/` |
| `web-fix/pages-verification-index.jsx` | `pages/verification/index.jsx` |
| `web-fix/pages-tenant-verification-in-barmer.jsx` | `pages/tenant-verification-in-barmer/index.jsx` |
| `web-fix/pages-owner-verification-in-barmer.jsx` | `pages/owner-verification-in-barmer/index.jsx` |
| `web-fix/pages-my-verification-orders.jsx` | `pages/my-verification-orders/index.jsx` |
| `web-fix/pages-tenant-verification-redirect.jsx` | `pages/tenant-verification/index.jsx` |
| `web-fix/pages-owner-verification-redirect.jsx` | `pages/owner-verification/index.jsx` |
| `web-fix/pages-tenant-verification-in-citySlug.jsx` | `pages/tenant-verification-in-[citySlug]/index.jsx` |
| `web-fix/pages-owner-verification-in-citySlug.jsx` | `pages/owner-verification-in-[citySlug]/index.jsx` |

**Core patches (manual, not in installer):**

| Staging | Server |
|---------|--------|
| `web-fix/Header.jsx` | `src/components/layout/Header.jsx` (or equivalent path on server) |
| `web-fix/PropertyDetails.jsx` | stock property detail component path |
| `web-fix/plugins/property-detail-switcher/CustomPropertyDetailsPage.jsx` | custom layout path |

Re-apply core patches after wrteam web updates if those files are overwritten.

## Quick deploy script

```bash
bash scripts/deploy-trust-verification-v1.sh
```

Runs backup + admin install + partial web install. **Follow with Full web deploy** for redirect pages and core patches.

## Verify after deploy

```bash
curl -s "https://admin-homes.sukoon.group/api/trust-verification/packages?type=tenant&city=barmer"
curl -sL -o /dev/null -w "%{http_code}\n" "https://homes.sukoon.group/verification"
curl -sL -o /dev/null -w "%{http_code}\n" "https://homes.sukoon.group/tenant-verification-in-barmer"
curl -sL -o /dev/null -w "%{http_code}\n" "https://homes.sukoon.group/tenant-verification/"
```

## URLs (production)

| URL | Purpose |
|-----|---------|
| `/verification` | Hub — Barmer tenant vs owner |
| `/tenant-verification-in-barmer` | **Landlord flow** — verify a tenant |
| `/owner-verification-in-barmer` | **Tenant flow** — verify an owner |
| `/tenant-verification/` | Redirect → Barmer tenant page |
| `/owner-verification/` | Redirect → Barmer owner page |
| `/my-verification-orders` | Logged-in orders + PDF + pay later |
| Admin `/trust-verification` | Ops queue — **bookmark this** (no sidebar link yet) |

### URL naming

Paths name the **subject being verified**: `tenant-verification` = check on tenant (landlord orders); `owner-verification` = check on owner (tenant orders).

## API

| Method | Path | Auth |
|--------|------|------|
| GET | `/api/trust-verification/packages?type=tenant\|owner&city=barmer` | No |
| GET | `/api/trust-verification/cities` | No (returns Barmer only) |
| GET | `/api/trust-verification/payment-settings` | No |
| POST | `/api/trust-verification/orders` | Sanctum |
| GET | `/api/trust-verification/orders` | Sanctum |
| POST | `/api/trust-verification/orders/{id}/payment-intent` | Sanctum |
| POST | `/api/trust-verification/orders/{id}/confirm-payment` | Sanctum |

## Workflow

1. User submits order on web.
2. Pay via Cashfree (optional at submit or later from my orders) **or** admin marks `paid`/`waived` offline.
3. Admin updates check items at `/trust-verification`.
4. Admin uploads PDF → user emailed.
5. User downloads from `/my-verification-orders`.

## Deployed features

| Version | Features |
|---------|----------|
| v1 | Packages, wizard, admin queue, manual PDF, offline payment |
| v2 | Cashfree, emails (submit + report), pay later |
| v3 | Header menu, verify-owner on property detail (standard + custom layout) |
| Barmer-only | All cities forced to `barmer`; dynamic city URLs redirect |

## Seeded packages (Barmer)

- Tenant Basic ₹999 · Standard ₹1999
- Owner Basic ₹999 · Standard ₹1999

## Ops reminder

- **Admin queue:** Users → **Trust Verification** (or bookmark `/trust-verification`).
- **Paid orders missing `payment_transaction_id`:** See status report **§4.1** — run Step 1 check SQL, reconcile from Cashfree dashboard, **one UPDATE at a time**, re-run Step 1 after each update (do not batch).

## Security note

PDF reports are on the Laravel **public** disk. See §4.9 in the status report — plan authenticated download before scaling volume.

## Not yet

- Hindi UI
- Admin package CRUD
- Multi-city (when business approves)
- Flutter app
- Secure signed PDF URLs

## Composer autoload

If classes are not found:

```json
"App\\\\Plugins\\\\": "app/Plugins/"
```

Then `composer dump-autoload` on admin server.

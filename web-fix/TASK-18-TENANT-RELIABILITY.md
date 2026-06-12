# Task 18 — Tenant Reliability Summary (Owner Visible)

**Plugin:** `plugins/TrustVerification` only. **No core edits.**  
**Purpose:** Simple owner confidence for property owners screening tenants — not the fraud/risk engine, not full trust breakdown, no PII.

## Visibility model

| Layer | Who sees what |
|-------|----------------|
| Trust (`public-trust`) | Public trust score + badges (when enabled) |
| Risk (`tv_risk_*`) | Admin only (Task 17) |
| **Tenant reliability** | Property owners via public API + admin management |

## Database

Migration: `database/migrations/2026_05_30_000016_create_tv_tenant_reliability_table.php`

| Column | Notes |
|--------|--------|
| `customer_id` | Unique tenant customer |
| `verification_completion` | 0–100 from four owner-safe checks |
| `reliability_level` | `basic_tenant` · `verified_tenant` · `trusted_tenant` · `premium_trusted_tenant` |
| `verification_status` | `pending` · `partial` · `complete` |
| `public_visible` | Admin toggle (default true on create) |
| `summary_json` | Internal snapshot (checks only in public API) |
| `last_calculated_at` | Refresh timestamp |

Derived from completed **tenant** verification orders + check items — no duplicate fraud storage.

## Reliability levels (from completion %)

| Completion | Label |
|------------|--------|
| 0–30 | Basic Tenant |
| 31–60 | Verified Tenant |
| 61–85 | Trusted Tenant |
| 86–100 | Premium Trusted Tenant |

## Owner-safe checks (API + UI)

- Identity Verified  
- Reference Checked  
- Police Verification Completed  
- Documents Verified  

**Never exposed:** Aadhaar, PAN, police certificate files, reference phones, fraud signals, risk score, trust breakdown, admin notes, device/IP, rejected doc history.

## Service

`Services/TrustVerificationTenantReliabilityService.php`

| Method | Role |
|--------|------|
| `calculateReliability()` | Persist row from latest completed tenant order |
| `buildSummary()` | Four checks + internal `trust_verified` (not in public payload) |
| `verificationCompletion()` | % from checks |
| `publicSafeData()` | Owner API payload |
| `refresh()` / `bulkRefreshAll()` | Recalculate |
| `onOrderEvent()` | Hooked from `TrustVerificationTrustBadgeService::onOrderEvent()` |

Setting: `tenant_reliability_public_enabled` (default on if unset).

## Artisan

```bash
php artisan tenant-reliability:refresh          # all tenant orders
php artisan tenant-reliability:refresh 19       # one customer
```

**Cron (recommended nightly, after risk/trust jobs):**

```cron
15 2 * * * cd /www/wwwroot/admin-homes && php artisan tenant-reliability:refresh >> /var/log/tv-tenant-reliability.log 2>&1
```

## Admin UI

**Nav:** Trust Verification → **Tenant Reliability**

| Route | Action |
|-------|--------|
| `GET /trust-verification/reliability` | List (customer, level, %, visibility) |
| `GET /trust-verification/reliability/{customerId}` | Detail + owner-safe preview + order history |
| `POST /trust-verification/reliability/{customerId}/refresh` | Manual refresh |
| `POST /trust-verification/reliability/bulk-refresh` | Bulk refresh |
| `POST /trust-verification/reliability/{customerId}/public` | Toggle `public_visible` |

## Public API (owner-safe)

| Method | Path | Auth |
|--------|------|------|
| GET | `/api/trust-verification/tenant-reliability/{customerId}` | None (same as `public-trust`) |

**Response `data` (when available):**

```json
{
  "customer_id": 19,
  "reliability_level": "premium_trusted_tenant",
  "reliability_label": "Premium Trusted Tenant",
  "verification_completion": 100,
  "verification_status": "complete",
  "overall_label": "Premium Trusted Tenant",
  "checks": [
    { "key": "identity", "label": "Identity Verified", "verified": true },
    { "key": "reference", "label": "Reference Checked", "verified": true },
    { "key": "police", "label": "Police Verification Completed", "verified": true },
    { "key": "documents", "label": "Documents Verified", "verified": true }
  ],
  "last_calculated_at": "2026-05-24T12:00:00+00:00"
}
```

Returns `data: null` when no tenant order, public disabled, or `public_visible` false.

**Isolation:** No `risk_*`, `trust_score`, `breakdown`, `fraud`, `aadhaar`, `pan`, `admin_note`.

## Frontend (Homes)

Copy `web-fix/plugins/trust-verification/` → `src/plugins/trust-verification/`.

| File | Role |
|------|------|
| `ui/SukoonTenantReliabilityCard.jsx` | Compact card |
| `ui/SukoonTenantReliabilitySection.jsx` | Fetch + sanitize + render |
| `ui/sanitizeTenantReliability.js` | Allow-list fields |
| `ui/tenantReliabilityCache.js` | Deduped fetch |
| `trustVerificationApi.js` | `getTenantReliabilityProfile()` |
| `ui/trustVerificationPremium.module.css` | `.tenantReliability*` styles |
| `homes-frontend/.../OwnerDetailsCard.jsx` | Optional `tenantCustomerId` prop |

**Wiring:** Pass `tenantCustomerId={applicantCustomerId}` from inquiry/screening/booking flows. Do **not** pass property `added_by` (that remains `SukoonOwnerTrustSection`).

Deploy script: `web-fix/deploy-task-18-tenant-reliability.sh`

## Files (plugin backend)

**New**

- `database/migrations/2026_05_30_000016_create_tv_tenant_reliability_table.php`
- `Models/TvTenantReliability.php`
- `Services/TrustVerificationTenantReliabilityService.php`
- `Console/RefreshTenantReliabilityCommand.php`
- `Http/Controllers/Admin/TrustVerificationTenantReliabilityAdminController.php`
- `views/admin/reliability/index.blade.php`, `show.blade.php`

**Updated**

- `routes/api.php`, `routes/web.php`
- `Http/Controllers/Api/TrustVerificationApiController.php` — `publicTenantReliability`
- `Services/TrustVerificationTrustBadgeService.php` — reliability hook
- `views/admin/partials/nav.blade.php`
- `TrustVerificationServiceProvider.php`

## Deploy (admin-homes + homes)

```bash
# On server (REPO=/root/cursr or your path)
bash web-fix/deploy-task-18-tenant-reliability.sh
```

Or manually:

```bash
# Admin
rsync -a plugins/TrustVerification/ /www/wwwroot/admin-homes/app/Plugins/TrustVerification/
cd /www/wwwroot/admin-homes
php artisan migrate --path=app/Plugins/TrustVerification/database/migrations/2026_05_30_000016_create_tv_tenant_reliability_table.php --force
php artisan tenant-reliability:refresh
php artisan optimize:clear

# Homes — copy web-fix plugin UI + OwnerDetailsCard, npm run build, pm2 restart
```

## QA checklist

- [ ] Migration `tv_tenant_reliability` exists; `tenant-reliability:refresh` completes without error
- [ ] Admin → Tenant Reliability list/detail; bulk + per-customer refresh
- [ ] `GET /api/trust-verification/tenant-reliability/{id}` — only allow-listed fields; `data: null` when hidden
- [ ] Same URL has **no** `risk_`, `fraud`, `trust_score`, `breakdown`, phone, Aadhaar, PAN
- [ ] `GET /api/trust-verification/public-trust/{id}` unchanged (no reliability mixed in)
- [ ] Risk Center still admin-only; payments/reports unaffected
- [ ] Homes card renders when parent passes `tenantCustomerId` and API returns data
- [ ] Card hidden when `public_visible` false or no tenant verification

## Rollback

```bash
cd /www/wwwroot/admin-homes
php artisan migrate:rollback --path=app/Plugins/TrustVerification/database/migrations/2026_05_30_000016_create_tv_tenant_reliability_table.php
# Remove plugin reliability routes/controllers/service files from deploy backup
# Revert homes UI files from pre-Task-18 backup; rebuild PM2 app
```

Restore prior plugin tarball if full revert needed (see Task 17B backup pattern).

## Screenshots (after deploy)

Capture to `web-fix/screenshots/task-18/`:

1. Admin Tenant Reliability index  
2. Customer detail with owner-safe preview  
3. Homes compact card (owner view with `tenantCustomerId`)  
4. API JSON snippet (redacted customer id)

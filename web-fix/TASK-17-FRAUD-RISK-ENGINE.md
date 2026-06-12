# Task 17 — Fraud Signals + Risk Engine

**Plugin:** `plugins/TrustVerification` only (no core edits).  
**Visibility:** Admin internal only — never on Homes, never on `public-trust` API.

## Database

Migration: `database/migrations/2026_05_29_000015_create_tv_fraud_risk_tables.php`

| Table | Purpose |
|-------|---------|
| `tv_risk_signals` | Individual fraud signals (auto + manual), timeline |
| `tv_risk_profiles` | Per-customer risk score, level, manual override |

Run on admin-homes:

```bash
cd /www/wwwroot/admin-homes
php artisan migrate --path=app/Plugins/TrustVerification/database/migrations/2026_05_29_000015_create_tv_fraud_risk_tables.php
```

## Auto signals (points)

| Signal | Points |
|--------|--------|
| `duplicate_mobile` | 20 |
| `duplicate_id` | 40 |
| `too_many_verification_requests` | 15 (>3 orders / 30d default) |
| `rejected_document_upload` | 10 |
| `police_verification_rejected` | 30 |
| `reference_failed` | 20 |
| `multiple_accounts_same_device` | 35 (consent IP match, 90d) |
| `frequent_profile_edits` | 5 (activity threshold / 7d) |
| `admin_manual_flag` | Custom 1–100 (manual source) |

**Risk levels:** 0–20 low · 21–40 medium · 41–70 high · 71–100 critical  
**Score:** Sum of signal points + `manual_override`, clamped 0–100.

## Services

- `Services/TrustVerificationFraudRiskService.php` — detect, refresh, profile, manual flag
- Hooks via `TrustVerificationTrustBadgeService::onOrderEvent()` (trust + risk refresh together)
- Extra hooks: order create, document upload rejected (API)

## Artisan

```bash
php artisan risk:refresh          # all customers with TV orders
php artisan risk:refresh 15       # single customer
```

**Cron (recommended nightly):**

```cron
0 2 * * * cd /www/wwwroot/admin-homes && php artisan risk:refresh >> /var/log/tv-risk-refresh.log 2>&1
```

## Admin UI

**Nav:** Trust Verification → **Risk Center**

| Route | Screen |
|-------|--------|
| `GET /trust-verification/risk/profiles` | Risk profiles (filters: level, duplicate phone, rejected police, failed reference) |
| `GET /trust-verification/risk/profiles/{id}` | Profile, timeline, manual override, manual flag |
| `GET /trust-verification/risk/signals` | All signals |
| `GET /trust-verification/risk/profiles/{id}/api` | JSON (admin session) |

**Order detail:** accordion “Fraud risk (admin only)” with score, level, signals, link to Risk Center.

## API (admin only)

- `GET /trust-verification/risk/profiles/{customerId}/api` — JSON profile + signals (requires admin auth)
- **No** public or Sanctum customer routes for risk
- `GET /api/trust-verification/public-trust/{id}` unchanged (trust only)

## Files added/updated

**New**

- `database/migrations/2026_05_29_000015_create_tv_fraud_risk_tables.php`
- `Models/TvRiskSignal.php`, `Models/TvRiskProfile.php`
- `Services/TrustVerificationFraudRiskService.php`
- `Console/RefreshRiskCommand.php`
- `Http/Controllers/Admin/TrustVerificationRiskAdminController.php`
- `views/admin/risk/profiles/index.blade.php`, `show.blade.php`
- `views/admin/risk/signals/index.blade.php`
- `views/admin/partials/risk-nav.blade.php`, `risk-order-panel.blade.php`

**Updated**

- `TrustVerificationServiceProvider.php` — register `risk:refresh`
- `routes/web.php` — risk routes
- `views/admin/partials/nav.blade.php` — Risk Center tab
- `views/admin/show.blade.php` — risk panel include
- `Http/Controllers/Admin/TrustVerificationAdminController.php` — risk data on order show
- `Services/TrustVerificationTrustBadgeService.php` — call fraud refresh on order events
- `Http/Controllers/Api/TrustVerificationApiController.php` — risk refresh on create / upload reject

## Deploy

```bash
# Copy plugin to admin-homes (same as other TV deploys)
rsync -av plugins/TrustVerification/ srv:/www/wwwroot/admin-homes/app/Plugins/TrustVerification/
php artisan migrate --path=app/Plugins/TrustVerification/database/migrations/2026_05_29_000015_create_tv_fraud_risk_tables.php
php artisan risk:refresh
```

## QA checklist

- [ ] Migration runs without error
- [ ] `risk:refresh` completes; `tv_risk_profiles` populated for customers with orders
- [ ] Risk Center lists profiles; filters work (critical/high, duplicate phone, etc.)
- [ ] Customer risk show: timeline, manual override, manual flag add/remove
- [ ] Order detail shows fraud panel; **not** visible on Homes frontend
- [ ] `public-trust` API response has **no** `risk_score` / `risk_level` fields
- [ ] Trust score refresh still works after order complete / police / reference update
- [ ] Payments, reports, police, references unchanged (smoke one order E2E)
- [ ] Duplicate mobile: second customer with same phone → signal on refresh
- [ ] Police rejected → `police_verification_rejected` signal
- [ ] Manual flag increases score; delete manual flag recalculates

## Rollback

```bash
php artisan migrate:rollback --path=app/Plugins/TrustVerification/database/migrations/2026_05_29_000015_create_tv_fraud_risk_tables.php
# Remove Risk Center routes/views from deployed plugin or restore previous plugin tarball
# Remove cron line for risk:refresh
```

Trust and order flows continue if migration is rolled back (risk code paths try/catch via `onOrderEvent`).

## Screenshots (admin)

Capture after deploy:

1. Risk profiles index with level filter  
2. Customer risk profile + timeline  
3. Order detail — “Fraud risk (admin only)” accordion  

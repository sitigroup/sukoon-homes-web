# Task 16 — Sukoon Trust Score + Badge System

Plugin-only implementation under `plugins/TrustVerification/`. No core Laravel/Next.js file edits required for backend; frontend helpers live in `web-fix/plugins/trust-verification/` for copy into the homes app.

## Database

Migration: `database/migrations/2026_05_28_000014_create_tv_trust_badge_tables.php`

| Table | Purpose |
|-------|---------|
| `tv_trust_scores` | Per-customer score 0–100, breakdown JSON, manual adjustment, public flag |
| `tv_trust_badges` | Badge catalog (slug, name, priority, auto_assign, icon, color) |
| `tv_customer_badges` | Customer ↔ badge assignments |

Seeds 12 default badges and `tv_settings` keys: `trust_public_profile_enabled`, `trust_score_weights`.

## Score weights (default)

| Component | Points |
|-----------|--------|
| Identity (`id_verification` pass) | 20 |
| Address (`address_validation` / `address_match`) | 15 |
| Reference (`reference_check` pass) | 20 |
| Police (`criminal_check` pass or police `completed`) | 25 |
| Documents (required docs / `ownership_docs`) | 10 |
| Admin approval (completed order + report) | 5 |
| No rejected checks | 5 |

Final score = `clamp(0, 100, raw_total + manual_adjustment)`. Manual adjustment wins on top of raw total.

## Service

`Services/TrustVerificationTrustBadgeService.php`

- `calculate()` / `refreshScore()`
- `assignBadge()` / `removeBadge()`
- `manualAdjustment()`
- `evaluateAutoBadges()`
- `formatForApi()` — profile + public trust
- `bulkRefreshAll()`

### Triggers (non-breaking hooks)

| Event | Location |
|-------|----------|
| Report uploaded / order completed | `TrustVerificationService::storeReportFile()` |
| Admin status → completed | `TrustVerificationAdminController::updateStatus()` |
| Checks updated | `TrustVerificationAdminController::updateChecks()` |
| Reference sync | `TrustVerificationReferenceService::syncReferenceCheckItem()` |
| Police status / update | `TrustVerificationPoliceVerificationService` |

## Artisan command

```bash
php artisan trust:refresh-score          # all customers with completed orders
php artisan trust:refresh-score 123      # single customer ID
```

**Cron (night job):**

```cron
0 2 * * * cd /www/wwwroot/admin-homes && sudo -u www php artisan trust:refresh-score >> /var/log/tv-trust-refresh.log 2>&1
```

Queue-safe: command runs synchronously in batches of 100; no queue worker required.

## Admin UI

**Trust Verification → Trust Badges** (settings permission)

| Route | Page |
|-------|------|
| `GET /trust-verification/trust/badges` | Badge management |
| `GET /trust-verification/trust/rules` | Score weights + public toggle |
| `GET /trust-verification/trust/customers` | Customer trust list |
| `GET /trust-verification/trust/customers/{id}` | Breakdown, manual override, assign/revoke |

Actions: recalculate, bulk refresh, manual adjustment, public visibility, assign/revoke badge.

## API

| Method | Path | Auth |
|--------|------|------|
| GET | `/api/trust-verification/trust-profile` | Sanctum (my profile) |
| GET | `/api/trust-verification/public-trust/{customerId}` | Public (if enabled + customer `public_visible`) |

## Frontend (web-fix — deploy to homes `src/`)

| File | Use |
|------|-----|
| `ui/SukoonTrustCard.jsx` | Trust score + breakdown + badges |
| `ui/SukoonTrustBadges.jsx` | Badge chips row |
| `ui/SukoonTrustedOwnerBadge.jsx` | Property card / owner detail |
| `TrustProfileSection.jsx` | My profile section |
| `trustVerificationApi.js` | `getTrustVerificationTrustProfile`, `getPublicTrustProfile` |

**Wire examples:**

```jsx
// Property detail — owner trust
import SukoonTrustedOwnerBadge from "@/plugins/trust-verification/ui/SukoonTrustedOwnerBadge";
<SukoonTrustedOwnerBadge customerId={propertyDetails?.added_by} />

// My profile
import TrustProfileSection from "@/plugins/trust-verification/TrustProfileSection";
<TrustProfileSection />
```

## Changed files (plugin)

### New

- `database/migrations/2026_05_28_000014_create_tv_trust_badge_tables.php`
- `Models/TvTrustBadge.php`, `TvTrustScore.php`, `TvCustomerBadge.php`
- `Services/TrustVerificationTrustBadgeService.php`
- `Console/RefreshTrustScoreCommand.php`
- `Http/Controllers/Admin/TrustVerificationTrustAdminController.php`
- `views/admin/trust/badges/index.blade.php`, `form.blade.php`
- `views/admin/trust/rules/index.blade.php`
- `views/admin/trust/customers/index.blade.php`, `show.blade.php`
- `views/admin/partials/trust-nav.blade.php`

### Modified

- `TrustVerificationServiceProvider.php` — register command
- `routes/web.php`, `routes/api.php`
- `views/admin/partials/nav.blade.php`
- `Services/TrustVerificationService.php`
- `Services/TrustVerificationReferenceService.php`
- `Services/TrustVerificationPoliceVerificationService.php`
- `Http/Controllers/Admin/TrustVerificationAdminController.php`
- `Http/Controllers/Api/TrustVerificationApiController.php`

### Frontend (web-fix)

- `plugins/trust-verification/trustVerificationApi.js`
- `plugins/trust-verification/ui/SukoonTrustCard.jsx`
- `plugins/trust-verification/ui/SukoonTrustBadges.jsx`
- `plugins/trust-verification/ui/SukoonTrustedOwnerBadge.jsx`
- `plugins/trust-verification/TrustProfileSection.jsx`
- `plugins/trust-verification/ui/trustVerificationPremium.module.css` (trust card styles)

## Deploy

```bash
# On server (admin-homes)
cd /www/wwwroot/admin-homes
sudo -u www php artisan migrate --force
sudo -u www php artisan optimize:clear
sudo -u www php artisan view:clear
chown -R www:www app/Plugins/TrustVerification
```

Copy `web-fix/plugins/trust-verification/*` trust UI files into homes Next.js `src/plugins/trust-verification/` and rebuild PM2 app.

**Never** run `php artisan config:cache`.

## QA checklist

- [ ] Migration runs; 12 badges in `tv_trust_badges`
- [ ] Complete order #17 → customer score recalculates; breakdown shows components
- [ ] Reference verified → score/badge refresh (no 500 on order page)
- [ ] Police completed → criminal_check + police badge
- [ ] Admin: Trust Badges → edit badge priority/description
- [ ] Admin: Trust rules → change weights, toggle public off → API returns null for public trust
- [ ] Admin: Customer trust → manual +10 adjustment → final score updates
- [ ] Admin: Assign `Admin Approved` badge manually; revoke works
- [ ] Bulk refresh completes without timeout
- [ ] `GET /api/trust-verification/trust-profile` (logged-in customer)
- [ ] `GET /api/trust-verification/public-trust/{ownerId}` when public on
- [ ] Orders, payments, reports, police, reference flows unchanged (smoke test)
- [ ] `php artisan trust:refresh-score` runs OK

## Rollback

1. Disable public trust: Admin → Trust rules → uncheck public visibility.
2. Remove cron for `trust:refresh-score`.
3. Roll back migration (destroys trust data):

```bash
sudo -u www php artisan migrate:rollback --step=1
```

4. Remove plugin files listed above or restore previous plugin tarball.
5. `optimize:clear` + `view:clear`.
6. Remove frontend trust components from property/profile pages if deployed.

Hooks are try/catch wrapped — removing service calls only stops score updates; orders still work.

## Screenshots (capture after deploy)

1. Admin → Trust Badges list  
2. Admin → Customer trust detail (score 95/100 + breakdown)  
3. API JSON `trust-profile`  
4. Property page with Trusted Owner badge (after wiring `SukoonTrustedOwnerBadge`)

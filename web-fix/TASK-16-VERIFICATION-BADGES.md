# Task 16 — Sukoon Verification Badge System (issued SVO / SVT)

Plugin-only. Does not replace the trust **score** catalog (`tv_trust_badges` from migration `2026_05_28_000014`).

## Physical table

**`tv_verification_badges`** — issued certificates per completed order.

| Column | Purpose |
|--------|---------|
| `customer_id`, `order_id` | Owner of badge; one badge per order (unique `order_id`) |
| `badge_type` | `owner` → Sukoon Verified Owner · `tenant` → Sukoon Verified Tenant |
| `badge_number` | `SVO-2026-000001` or `SVT-2026-000001` |
| `status` | `pending`, `verified`, `expired`, `revoked` |
| `issued_at`, `expires_at`, `revoked_at`, `revoke_reason` | Lifecycle |
| `metadata` | Order number, admin id, auto flag |

## Auto-issue rules

When order events fire (`TrustVerificationIssuedBadgeService::onOrderEvent`):

- Order **completed**
- Payment **paid** or **waived**
- Required **documents** complete
- All non-`na` checks **pass**
- **Reference** check complete (if package includes it)
- **Police** completed (if package requires it)

Creates **verified** badge immediately when eligible; otherwise no row (admin can issue manually as `pending`).

Default validity: **12 months** from issue.

## Visibility

| Badge | Property cards | Public API | Profile / My orders |
|-------|----------------|------------|---------------------|
| **Owner (SVO)** | Yes — `sukoon_verified_owner` on `public-trust` | Yes | Yes |
| **Tenant (SVT)** | **No** | **No** | Yes (owner-only context) |

## Admin

**URL:** `https://admin-homes.sukoon.group/trust-verification/verification-badges`

Nav: **Trust Verification → Badges** (score catalog moved to **Trust Score** tab).

| Action | Route |
|--------|--------|
| List / filter | `GET …/verification-badges` |
| Detail | `GET …/verification-badges/{id}` |
| Issue from order | `POST …/verification-badges/orders/{order}/issue` |
| Mark verified | `POST …/verification-badges/{id}/verify` |
| Revoke | `POST …/verification-badges/{id}/revoke` |
| Renew | `POST …/verification-badges/{id}/renew` |
| Certificate | `GET …/verification-badges/{id}/download` |

Order detail includes **Verification badge** panel.

## Customer API

| Method | Path |
|--------|------|
| GET | `/api/trust-verification/verification-badges` |
| GET | `/api/trust-verification/orders/{order}/verification-badge` |
| GET | `/api/trust-verification/verification-badges/{id}/download` |

`formatOrder` includes `verification_badge`.  
`trust-profile` includes `verification_badges`.  
`public-trust/{id}` includes `sukoon_verified_owner` only (no tenant).

## Audit (`tv_audit_logs`)

- `badge_issued`
- `badge_downloaded`
- `badge_revoked`
- `badge_renewed`

## Deploy

```bash
cd /www/wwwroot/admin-homes
php artisan migrate --path=app/Plugins/TrustVerification/database/migrations/2026_05_31_000017_create_tv_verification_badges_table.php --force
php artisan optimize:clear
```

Sync plugin + `web-fix/plugins/trust-verification` to Homes; rebuild PM2.

Backfill badges for existing completed orders:

```bash
php artisan tinker
# foreach completed+paid orders: TrustVerificationIssuedBadgeService::tryAutoIssue($order);
```

Or complete/update an order in admin to trigger hooks.

## Test badge number

After first owner issue in 2026: **`SVO-2026-000001`**  
After first tenant issue: **`SVT-2026-000001`**

## QA table

| # | Case | Expected |
|---|------|----------|
| 1 | Completed paid order, all checks pass | Auto `verified` badge, correct SVO/SVT prefix |
| 2 | Revoked badge | Not shown as verified; download 404 |
| 3 | Expired badge | Status `expired`; not on public owner card |
| 4 | Tenant badge | Visible on My orders / profile; **not** on property card |
| 5 | Owner badge | `sukoon_verified_owner` on public-trust + property card label |
| 6 | Customer API | Cannot PATCH badge; download only own verified badge |
| 7 | Admin revoke | Audit `badge_revoked`; frontend hides verified state |
| 8 | Certificate download | PDF if Dompdf present, else HTML; audit `badge_downloaded` |

## Changed files (plugin)

- `database/migrations/2026_05_31_000017_create_tv_verification_badges_table.php`
- `Models/TvVerificationBadge.php`
- `Services/TrustVerificationIssuedBadgeService.php`
- `Http/Controllers/Admin/TrustVerificationIssuedBadgeAdminController.php`
- `Http/Controllers/Api/TrustVerificationApiController.php` (badge endpoints)
- `Services/TrustVerificationTrustBadgeService.php` (hook + API fields)
- `Services/TrustVerificationService.php` (`verification_badge` on orders)
- `Services/TrustVerificationAuditLogService.php`
- `Models/TvOrder.php`, `routes/web.php`, `routes/api.php`
- `views/admin/verification-badges/*`, `views/admin/partials/verification-badge-order-panel.blade.php`
- `views/badge/certificate.blade.php`
- `views/admin/partials/nav.blade.php`, `views/admin/show.blade.php`

## Frontend (web-fix)

- `ui/VerificationOrderBadgePanel.jsx`, `ui/VerificationIssuedBadgesList.jsx`
- `ui/PropertyCardTrustLabel.jsx` (owner SVO only)
- `TrustProfileSection.jsx`, `pages-my-verification-orders.jsx`
- `trustVerificationApi.js`

## Screenshots (capture after deploy)

1. Admin → Badges list with `SVO-2026-000001`
2. Order detail → Verification badge panel
3. My Verification Orders → Download badge
4. Property card → Sukoon Verified Owner
5. Profile → Verification badges list

# TASK 15 — Rajasthan Police Verification Workflow

## Migration

`2026_05_27_000013_create_tv_police_verifications_table.php`

Table: `tv_police_verifications` (one row per order, unique `order_id`).

## Changed files

### Backend (`plugins/TrustVerification/`)

| File | Change |
|------|--------|
| `database/migrations/2026_05_27_000013_create_tv_police_verifications_table.php` | New table |
| `Models/TvPoliceVerification.php` | New model |
| `Models/TvOrder.php` | `policeVerification()` relation |
| `Services/TrustVerificationPoliceVerificationService.php` | Core workflow, gating, sync criminal_check |
| `Services/TrustVerificationPoliceDocumentService.php` | Private uploads/downloads |
| `Services/TrustVerificationPoliceUploadValidator.php` | PDF/JPG/PNG validation |
| `Services/TrustVerificationDocumentUploadValidator.php` | Optional `$maxBytes` param |
| `Services/TrustVerificationAuditLogService.php` | Police audit actions |
| `Services/TrustVerificationService.php` | `formatOrder()` police fields |
| `Services/TrustVerificationOrderTimestampsService.php` | Police timestamps |
| `Services/TrustVerificationAutomationService.php` | Risk summary police section |
| `Services/TrustVerificationContentDefaults.php` | CMS wizard/report blocks |
| `Http/Controllers/Admin/TrustVerificationAdminController.php` | Admin CRUD + downloads |
| `Http/Controllers/Api/TrustVerificationApiController.php` | Customer API |
| `routes/web.php` | Admin routes |
| `routes/api.php` | Customer API routes |
| `views/admin/partials/police-verification-panel.blade.php` | Admin card |
| `views/admin/show.blade.php` | Include panel |

### Frontend (`web-fix/plugins/trust-verification/`)

| File | Change |
|------|--------|
| `trustVerificationPoliceUtils.js` | Package/order helpers |
| `trustVerificationApi.js` | Police API + downloads |
| `ui/PoliceVerificationDetailsForm.jsx` | Customer form |
| `ui/OrderPoliceVerificationPanel.jsx` | My Orders panel |
| `ui/trustVerificationTimelineUtils.js` | Verification step detail |
| `ui/index.js` | Exports |
| `TrustVerificationPage.jsx` | Optional wizard step fields |
| `trustVerificationContentFallback.js` | CMS fallbacks |
| `web-fix/pages-my-verification-orders.jsx` | Panel + content hook |

## Admin URL

Order detail (example):

`https://admin-homes.sukoon.group/public/trust-verification/orders/{id}`

Police Verification card appears when order package includes `criminal_check`, `police_verification`, `tenant_verification`, or feature label/key contains police/criminal/civil.

## API endpoints (auth:sanctum)

| Method | Path |
|--------|------|
| GET | `/api/trust-verification/orders/{order}/police-verification` |
| POST | `/api/trust-verification/orders/{order}/police-verification` |
| POST | `/api/trust-verification/orders/{order}/police-verification/acknowledgement` |
| GET | `/api/trust-verification/orders/{order}/police-verification/acknowledgement/download` |
| GET | `/api/trust-verification/orders/{order}/police-verification/certificate/download` |

`formatOrder()` also includes `police_verification_enabled`, `police_verification`, `police_verification_report`.

## Test order

Use any order with **Criminal record check** included (e.g. tenant packages with `criminal_check` feature). Order **18** (`TV-EYMTZLKB` area) or create a new tenant verification order in Barmer.

## Manual QA checklist

| # | Test | Expected |
|---|------|----------|
| 1 | Open order without criminal/police feature | No police section (admin or customer) |
| 2 | Open order with criminal_check | Police section visible |
| 3 | Customer submits station + mobile | Status → submitted, audit log |
| 4 | Customer uploads acknowledgement | File in private storage, audit log |
| 5 | Admin sees Police Verification card | Form + quick status buttons |
| 6 | Admin Mark Submitted / Under Review | Status + timestamps update |
| 7 | Admin Mark Completed + certificate upload | criminal_check sync pass, customer can download cert |
| 8 | My Orders timeline | Verification detail shows police status text |
| 9 | Risk summary draft | Police Verification block appended |
| 10 | Unauthorized download | 403 for other customer / missing permission |
| 11 | `php artisan optimize:clear` | Passes |
| 12 | Web `npm run build` | Passes |

## Rollback

```bash
cd /www/wwwroot/admin-homes
php artisan migrate:rollback --step=1   # drops tv_police_verifications
# Remove plugin files if needed; redeploy previous TrustVerification tarball
php artisan optimize:clear
```

Existing orders without a police row are unaffected (`police_verification` returns not_submitted summary when feature applies, null when not).

## Notes

- **No** unofficial API or scraping — “Check Rajasthan Police Status” opens configurable official URL in new tab only.
- API-ready fields: `provider_reference_number`, `provider_status`, `provider_last_checked_at`, `provider_raw_response`.
- Storage: `storage/app/trust-verification/police/{order_id}/` (local disk, auth-gated downloads).

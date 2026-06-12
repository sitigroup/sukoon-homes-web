# Task 13C — Date & Time Across Trust Verification

## API `timestamps` object (ISO 8601, resolved server-side)

| Field | Source |
|-------|--------|
| `created_at` | `tv_orders.created_at` |
| `updated_at` | `tv_orders.updated_at` |
| `consent_given_at` | `tv_orders.consent_given_at` |
| `paid_at` | Audit `payment_webhook_marked_paid` or `admin_changed_payment_status` → paid/waived; fallback `updated_at` |
| `document_uploaded_at` | Latest active `tv_order_documents.created_at` |
| `verification_started_at` | Audit status → `in_progress`, else earliest automation run |
| `review_completed_at` | `tv_orders.completed_at` |
| `report_created_at` | `tv_reports.created_at` |
| `report_uploaded_at` | Audit `admin_uploaded_report`, else report file timestamps |
| `automation_started_at` | Earliest `tv_automation_runs.started_at` or `created_at` |
| `automation_completed_at` | Latest run `completed_at` |
| `sla_due_at` | `created_at` + package `delivery_hours` |

Display timezone: **Asia/Kolkata** — e.g. `24 May 2026, 6:42 PM`

## Changed files

**Plugin (API + admin)**
- `Services/TrustVerificationOrderTimestampsService.php` (new)
- `Services/TrustVerificationService.php`
- `Http/Controllers/Api/TrustVerificationApiController.php`
- `Http/Controllers/Admin/TrustVerificationAdminController.php`
- `views/admin/show.blade.php`
- `views/admin/index.blade.php`

**Frontend**
- `trustVerificationDateTime.js` (new)
- `ui/trustVerificationTimelineUtils.js`
- `ui/OrderProgressStrip.jsx`
- `ui/index.js`
- `pages-my-verification-orders.jsx`

## Deploy

```bash
# Backend plugin + frontend plugin + my-orders page; build + restart homes
```

## Test order

**TV-EYMTZLKB** (customer 15, order id 17)

## Screenshots

Capture after deploy:
1. My Orders — expanded timeline with step dates + “Last updated”
2. Admin order detail — Key timestamps card
3. Admin orders list — Created / SLA / Last updated / Overdue columns

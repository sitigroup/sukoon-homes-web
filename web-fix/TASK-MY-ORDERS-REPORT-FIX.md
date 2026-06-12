# Task — My Orders Report Download + Timeline Labels Fix

## Test order
**TV-EYMTZLKB** (order_id=17)

## Root cause (production investigation)

| Check | Result |
|-------|--------|
| `tv_orders.status` | `completed` |
| `tv_orders.payment_status` | `paid` |
| `tv_reports.file_path` | `trust-verification/reports/17/xHHeT2LqOKDNrIN0ffctNipjjrgRypH55KExZTnP.pdf` |
| File on disk | **Yes** — 96,670 bytes, `%PDF-` header, `www` readable |
| `reportFileExists()` | **yes** |
| API `download_available` | **yes** |

**Conclusion:** The PDF exists and is valid today. The user-facing **"Could not download report"** toast came from **frontend error handling**, not a missing file:

1. `downloadTrustVerificationReport()` throws `Error` with a specific message (invalid blob, JSON error body, etc.).
2. `handleDownload()` only read `e?.response?.data?.message` (Axios shape), **ignoring `e.message`**, and fell back to the generic string.

When the API returned 404/HTML or a non-PDF blob, users always saw **"Could not download report"** instead of the support message.

Secondary gap: completed + paid orders with **no PDF** had no `awaiting_upload` flag — timeline could show "Report Ready" current while download was impossible.

## Fixes

### Backend
- API download: validate file **before** audit log; return JSON 404 with support message
- `formatOrder`: add `report.awaiting_upload` for completed + paid + missing file
- Admin order detail: warning banner when completed but PDF missing
- Admin orders list: **No PDF** chip on completed paid rows without file

### Frontend
- `downloadTrustVerificationReport`: parse Axios blob errors; unified support message
- `handleDownload`: use `e.message` first
- Hide download unless `download_available`; show support copy when `awaiting_upload`
- `OrderProgressStrip`: step labels on desktop; mobile legend + current step
- Timeline labels: Submitted, Payment, Verification, Review, Report Ready

## Changed files

**Backend**
- `plugins/TrustVerification/Http/Controllers/Api/TrustVerificationApiController.php`
- `plugins/TrustVerification/Services/TrustVerificationService.php`
- `plugins/TrustVerification/Http/Controllers/Admin/TrustVerificationAdminController.php`
- `plugins/TrustVerification/views/admin/show.blade.php`
- `plugins/TrustVerification/views/admin/index.blade.php`

**Frontend**
- `web-fix/pages-my-verification-orders.jsx`
- `web-fix/plugins/trust-verification/trustVerificationApi.js`
- `web-fix/plugins/trust-verification/ui/OrderProgressStrip.jsx`
- `web-fix/plugins/trust-verification/ui/trustVerificationTimelineUtils.js`

**QA**
- `web-fix/tv-qa-order-report.php`

## Deploy
```bash
# From workspace — rsync plugin + frontend page, migrate not needed, build + restart
```

## Screenshots
Capture after deploy on `/my-verification-orders`:
1. Expanded order with labeled timeline (desktop)
2. Mobile compact legend + current step
3. Completed order with download OR support message (no generic error)

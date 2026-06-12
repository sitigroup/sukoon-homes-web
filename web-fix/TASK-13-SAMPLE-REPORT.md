# Task 13 — Sample Report Preview

## Summary

Customers can preview fictional sample verification PDFs before purchase. Admins manage tenant/owner samples under Trust Verification → Sample reports. Copy is CMS-editable on the Hub tab.

## Admin URL

- **List:** `https://admin-homes.sukoon.group/trust-verification/sample-reports`
- **Create:** `https://admin-homes.sukoon.group/trust-verification/sample-reports/create`
- **CMS copy (Hub):** `https://admin-homes.sukoon.group/trust-verification/content` → Hub tab  
  Keys: `hub.sample_report_title`, `hub.sample_report_description`, `hub.sample_report_button_text`

## Public API

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/trust-verification/sample-reports/public?type=tenant&city=barmer&package_id=` | Resolve best active sample + CMS labels |
| GET | `/api/trust-verification/sample-reports/{id}/download?inline=1` | PDF stream (preview; no download audit) |
| GET | `/api/trust-verification/sample-reports/{id}/download` | PDF download → audit `sample_report_downloaded` |
| POST | `/api/trust-verification/sample-reports/{id}/viewed` | Audit `sample_report_viewed` |

## Frontend locations

- `/verification` hub — tenant/owner sample buttons per city
- Package wizard — link above grid + button on each package card
- Modal: included checks list, Preview (iframe), Download sample PDF

## Security

- PDF only (admin `mimes:pdf`, max 10 MB)
- Magic bytes `%PDF`, min size, reject encrypted/script PDFs
- No order/customer PII in sample storage path
- Rate limits: download 40/hr/IP, view POST 80/hr/IP

## Audit actions

- `sample_report_viewed`
- `sample_report_downloaded`
- `admin_uploaded_sample_report`

## Changed files

### Backend (`plugins/TrustVerification/`)

- `database/migrations/2026_05_24_000010_create_tv_sample_reports_table.php`
- `Models/TvSampleReport.php`
- `Services/TrustVerificationSampleReportService.php`
- `Services/TrustVerificationAuditLogService.php` (new actions)
- `Services/TrustVerificationRateLimiterRegistrar.php`
- `Services/TrustVerificationContentDefaults.php` (hub CMS keys)
- `Http/Controllers/Admin/TrustVerificationSampleReportAdminController.php`
- `Http/Controllers/Api/TrustVerificationApiController.php`
- `routes/web.php`, `routes/api.php`
- `views/admin/sample-reports/index.blade.php`, `form.blade.php`
- `views/admin/partials/nav.blade.php`

### Frontend (`web-fix/plugins/trust-verification/`)

- `trustVerificationApi.js`
- `trustVerificationContentFallback.js`
- `ui/SampleReportModal.jsx`, `ui/SampleReportTrigger.jsx`
- `ui/VerificationPackageCard.jsx`, `ui/VerificationHubPremium.jsx`
- `ui/trustVerificationPremium.module.css`
- `ui/index.js`
- `TrustVerificationPage.jsx`

## Deploy

```bash
# Backend plugin + migrate
cd plugins/TrustVerification && tar ... # rsync to admin-homes
php artisan migrate --force
php artisan view:clear

# Frontend plugin
rsync web-fix/plugins/trust-verification → homes/src/plugins/trust-verification
npm run build && pm2 restart homes-sukoon

# Seed new CMS keys (optional)
php artisan db:seed --class=App\\Plugins\\TrustVerification\\Database\\Seeders\\TvContentBlockSeeder
# or admin: Content → Seed missing defaults
```

## Test plan

1. Admin: create tenant + owner sample PDFs (active, global scope).
2. API: `GET .../sample-reports/public?type=tenant` returns `data.id` and CMS fields.
3. Web: hub → View Sample Report → modal → Preview → Download.
4. Wizard: package card sample button opens modal scoped to package/city.
5. Audit: rows in `tv_audit_logs` for view/download actions.
6. Negative: upload non-PDF rejected; inactive sample not returned by public API.

## Screenshots

Capture after deploy:

1. Admin sample reports list
2. Hub with sample buttons
3. Modal with checks + PDF preview
4. Package card with sample CTA

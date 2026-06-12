# TASK 14 — Reference Check Module

## Scope
Plugin-only (`plugins/TrustVerification/` + `web-fix/plugins/trust-verification/`). No wrteam core edits.

## Features

### Customer
- **Reference form** fields: Name, Relation, Mobile, Email, Notes
- **Types:** Previous landlord, Employer, Family reference
- **Wizard** (Step 3 review): optional references when package includes `reference_check`
- **My Verification Orders:** submit/view references; status per contact
- **Timeline:** Verification step shows `reference_progress.summary` when applicable

### Admin
- **Reference check** panel on order detail
- **Status:** Pending, Contacted, Verified, Failed, No response
- **Internal:** admin notes, call outcome, contacted/verified datetime, `external_ref_id` (future API)

### API (future-ready)
- `GET /api/trust-verification/orders/{order}/references`
- `POST /api/trust-verification/orders/{order}/references`
- `POST /api/trust-verification/orders` accepts optional `references[]`
- `external_ref_id` column for vendor automation

## Database
- Migration: `2026_05_25_000011_create_tv_reference_contacts_table.php`
- Table: `tv_reference_contacts`

## Deploy

### Admin (Laravel)
```bash
# Copy plugin to app/Plugins/TrustVerification/
php artisan migrate
```

### Web
```bash
# rsync web-fix/plugins/trust-verification → src/plugins/trust-verification
npm run build && pm2 restart homes-sukoon
```

## Key files

| Layer | Path |
|-------|------|
| Migration | `plugins/TrustVerification/database/migrations/2026_05_25_000011_create_tv_reference_contacts_table.php` |
| Model | `Models/TvReferenceContact.php` |
| Service | `Services/TrustVerificationReferenceService.php` |
| API | `Http/Controllers/Api/TrustVerificationApiController.php` |
| Admin | `Http/Controllers/Admin/TrustVerificationAdminController.php`, `views/admin/partials/reference-check-panel.blade.php` |
| Frontend | `ui/ReferenceDetailsForm.jsx`, `ui/OrderReferencePanel.jsx`, `trustVerificationReferenceUtils.js` |

## QA
1. Create tenant order with package that includes reference check
2. Submit references from wizard or My Orders
3. Admin: update status, call outcome, notes — verify check item sync
4. Customer timeline shows reference progress on Verification step

# Task 17A — Rental Agreement Engine Foundation
**For:** Sukoon Homes dev team (Cursor)
**Status:** Ready to implement
**Scope:** Foundation only — migrations, services, admin index. No wizard yet.

---

## How to use this in Cursor

### Option A — Composer (recommended, fastest)
1. Open Cursor Composer: `Cmd+Shift+I` (Mac) / `Ctrl+Shift+I` (Windows)
2. Open `docs/CURSOR-COMPOSER-PROMPT-17A.md`
3. Select all → paste into Composer
4. Review each file Cursor creates before accepting
5. Run syntax check (see QA below)

### Option B — Chat with context
1. Open any file in the plugin
2. Cursor chat will read `.cursor/rules` automatically
3. Ask: "Create [specific file] for the Rental Agreement plugin"
4. Cursor already knows the rules — no need to re-explain constraints

### Option C — Manual + Cursor autocomplete
1. Copy files from the `app/Plugins/RentalAgreement/` folder in this zip
2. Place them at the correct paths in your project
3. Cursor will use `.cursor/rules` for autocomplete context

---

## File placement map

Drop everything relative to your Laravel project root (`admin-homes/`):

```
YOUR PROJECT ROOT (admin-homes/)
│
├── .cursor/
│   └── rules                          ← copy this first
│
└── app/
    └── Plugins/
        └── RentalAgreement/
            ├── RentalAgreementServiceProvider.php
            ├── Contracts/
            │   └── RentalAgreementProviderInterface.php
            ├── Http/
            │   └── Controllers/
            │       └── Admin/
            │           └── RentalAgreementAdminController.php
            ├── Models/
            │   ├── RentalAgreement.php
            │   ├── RentalAgreementAuditLog.php
            │   ├── RentalAgreementClause.php
            │   ├── RentalAgreementSelectedClause.php
            │   └── RentalAgreementSequence.php
            ├── Providers/
            │   └── ManualRentalAgreementProvider.php
            ├── Services/
            │   ├── RentalAgreementAuditService.php
            │   ├── RentalAgreementNumberService.php
            │   └── RentalAgreementPdfService.php
            ├── database/
            │   └── migrations/
            │       ├── 2026_05_26_000001_..._sequences_table.php
            │       ├── 2026_05_26_000002_..._agreements_table.php
            │       ├── 2026_05_26_000003_..._clauses_table.php
            │       ├── 2026_05_26_000004_..._selected_clauses_table.php
            │       └── 2026_05_26_000005_..._audit_logs_table.php
            ├── routes/
            │   └── web.php
            └── views/
                └── admin/
                    └── index.blade.php
```

---

## One manual step required

Add to `config/app.php` in the `providers` array:

```php
App\Plugins\RentalAgreement\RentalAgreementServiceProvider::class,
```

---

## Local QA before pushing

```bash
# 1. Syntax check every plugin PHP file
for f in $(find app/Plugins/RentalAgreement -name "*.php"); do php -l "$f"; done

# 2. Check no WRTeam core files were modified
git diff --name-only | grep -v "app/Plugins/RentalAgreement" | grep -v "config/app.php"
# Should output nothing (or only config/app.php for the provider registration)

# 3. Run migrations dry-run
php artisan migrate --pretend --path=app/Plugins/RentalAgreement/database/migrations

# 4. If pretend passes, run for real
php artisan migrate --force --path=app/Plugins/RentalAgreement/database/migrations
php artisan optimize:clear

# 5. Hit the admin index
curl -I https://admin-homes.sukoon.group/rental-agreements
# Expect: 200 or 302 (redirect to login)
```

---

## What Cursor should NOT do in this task

If Cursor tries to create any of these, reject and re-prompt:

- Any file in `node_modules/`, `vendor/`, or WRTeam core paths
- A `database/migrations/` file outside the plugin folder
- Any eSign, eStamp, DigiLocker, or Cashfree integration
- Any Next.js or frontend file
- Any seeder (legal review not done yet)
- A `RentalAgreementClauseSeeder` (blocked until legal sign-off)
- Installing composer packages (requires ops approval)
- Gold (#B89A4A) on a button background

---

## Cursor prompts for common follow-up tasks

These are safe to use in Cursor Chat after 17A is deployed:

**"Add the show/detail view for a single agreement"**
> Task 17B scope — do not do in 17A

**"Add the frontend wizard"**
> Task 17B scope — do not do in 17A

**"Generate the PDF"**
> Blocked until barryvdh/laravel-dompdf is approved and installed

**"Add clause seeder with default templates"**
> Blocked until LEGAL-REVIEW-CLAUSES-APPROVED.md is signed off

**"Add status change endpoint"**
> Task 17B scope

---

## Rollback

```bash
php artisan migrate:rollback \
  --path=app/Plugins/RentalAgreement/database/migrations \
  --step=5
php artisan optimize:clear
# Then remove provider from config/app.php
```

Safe to rollback until the first real production agreement is created.
After that: export data before any rollback.

---

## Next task after this is merged: Task 17B

- Full 7-step frontend wizard (Next.js)
- PDF generation (after barryvdh/laravel-dompdf approved)
- Admin show/edit/status-change views
- Secure download controller
- Signed copy upload endpoint
- Clause CRUD admin
- My Rental Agreements page (customer-facing)

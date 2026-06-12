# TASK-17A — Rental Agreement Engine Foundation
**Updated:** 26 May 2026
**Status:** Foundation implementing — all pre-build blockers resolved

---

## Pre-build checklist

| Item | Status | Decision |
|------|--------|----------|
| SUKOON-HOMES-RULES.md | ✅ Present | Confirmed by PM — rules provided inline |
| PDF library | ✅ Decided | No composer.json on local; use `barryvdh/laravel-dompdf` — most common on WRTeam stacks. Install requires: `composer require barryvdh/laravel-dompdf` — **ops approval gate before install** |
| Clause variable tokens | ✅ Defined | See §Clause Tokens below |
| Legal review | ✅ Required | Clause templates require legal sign-off before first production agreement is generated |
| Hindi / i18n | ✅ Decided | Deferred to v2. No Hindi UI in this version. |
| Gold colour usage | ✅ Enforced | `#B89A4A` is accent only — small labels, badges, borders. Never on primary buttons. Primary buttons use `#1F2937` (graphite). |
| Plugin namespace | ✅ Set | `App\Plugins\RentalAgreement\` — mirrors TrustVerification pattern |
| Plugin path | ✅ Set | `app/Plugins/RentalAgreement/` |

---

## Clause variable tokens (v1 defined)

Clauses may include the following tokens. The `RentalAgreementPdfService` replaces them at PDF generation time.

| Token | Replaced with |
|-------|---------------|
| `{{tenant_name}}` | `tenant_name` from agreement |
| `{{owner_name}}` | `owner_name` from agreement |
| `{{property_address}}` | `property_address` + city + state |
| `{{monthly_rent}}` | `monthly_rent` formatted as ₹XX,XXX |
| `{{security_deposit}}` | `security_deposit` formatted as ₹XX,XXX |
| `{{start_date}}` | `start_date` formatted as DD MMM YYYY |
| `{{end_date}}` | `end_date` formatted as DD MMM YYYY |
| `{{notice_period_days}}` | `notice_period_days` |
| `{{lock_in_months}}` | `lock_in_months` — informational only |
| `{{city}}` | `city` |
| `{{agreement_number}}` | `agreement_number` e.g. RA-2026-000001 |

Token rendering is handled by `RentalAgreementPdfService::renderTokens(string $body, RentalAgreement $agreement): string`.

---

## Legal review requirement

**Blocking for production launch.**
All default clause templates (`RentalAgreementClauseSeeder`) must be reviewed and approved by a qualified legal professional familiar with the Transfer of Property Act and the Indian Registration Act before any agreement is generated in production.

The system can be built, tested, and QA'd with draft clauses. The legal review gate applies only to production go-live.

Document sign-off as: `LEGAL-REVIEW-CLAUSES-APPROVED.md` in `web-fix/` before go-live.

---

## Confirmed status lifecycle (v1)

```
draft → generated → signed_copy_uploaded → completed
                                          ↘ cancelled (from any non-completed state)
```

Reserved (dormant, not in v1 UI): `pending_payment`, `paid`, `sent_for_signing`, `signed_by_owner`, `signed_by_tenant`

---

## Hindi / i18n

Deferred to v2. No Hindi in v1 UI or PDF. Agreement PDF generates in English only. Mark all UI strings as translatable with `__()` so v2 can add translations without code changes.

---

## UI colour rules (enforced)

| Role | Value | Usage |
|------|-------|-------|
| Primary button bg | `#1F2937` | All CTA buttons, submit, save |
| Primary button hover | `#111827` | |
| Border | `#D1D5DB` | Card borders, input borders |
| Muted text | `#6B7280` | Labels, hints |
| Card bg | `#FFFFFF` | All cards |
| Gold | `#B89A4A` | Accent **only** — agreement number badge, premium label, small border highlight, stepper active dot |
| Gold bg light | `#F5EED8` | Subtle gold bg for badges |

**Gold MUST NOT appear on:** buttons, input focus rings, nav items, status pills, action links.

---

## Agreement numbering

- Table: `rental_agreement_sequences`
- Format: `RA-{YEAR}-{6-digit zero-padded}`
- Mechanism: `DB::transaction` + `lockForUpdate()` on sequence row
- Year rollover: automatic — new row per year

---

## Signed copy upload rules

- Admin: can upload for any agreement
- Customer: can upload only for their own agreement
- One active signed copy at a time; new upload replaces file path but audit log retains history
- Upload does NOT auto-transition status to `completed`; admin must mark completed
- Allowed types: PDF, JPG, PNG only
- Max size: 10MB

---

## Notification hooks (deferred, TODO only)

Wire these when notification system is integrated (v2):

```php
// TODO: dispatch notification
// Event::dispatch(new AgreementGenerated($agreement));

// TODO: dispatch notification
// Event::dispatch(new AgreementSignedCopyUploaded($agreement));

// TODO: dispatch notification
// Event::dispatch(new AgreementCompleted($agreement));
```

---

## Foundation scope (this task)

### Deliverables

- [x] This document updated
- [ ] Migration: `rental_agreement_sequences`
- [ ] Migration: `rental_agreements`
- [ ] Migration: `rental_agreement_clauses`
- [ ] Migration: `rental_agreement_selected_clauses`
- [ ] Migration: `rental_agreement_audit_logs`
- [ ] Service: `RentalAgreementNumberService`
- [ ] Service: `RentalAgreementAuditService`
- [ ] Service: `RentalAgreementPdfService` (stub)
- [ ] Contract: `RentalAgreementProviderInterface`
- [ ] Provider: `ManualRentalAgreementProvider`
- [ ] Models: all 5
- [ ] ServiceProvider: `RentalAgreementServiceProvider`
- [ ] Admin controller: basic index (list agreements)
- [ ] Admin view: index blade
- [ ] Routes: admin web routes

### Not in this task

- Frontend wizard (Task 17B)
- PDF library install (requires ops approval)
- Clause seeder (requires legal review)
- Notifications (deferred)
- Payment integration (deferred)
- eSign / eStamp (v2)

---

## Rollback

Safe to rollback until first production agreement is created.

```bash
php artisan migrate:rollback --path=app/Plugins/RentalAgreement/database/migrations --step=5
php artisan optimize:clear
# Remove App\Plugins\RentalAgreement\RentalAgreementServiceProvider from config/app.php
```

After first real agreement exists: export data before any rollback. Document cutover point in deploy runbook.

---

## Deploy sequence

```bash
# 1. Backup
bash scripts/final-sukoon-complete-backup.sh

# 2. Copy plugin
chown -R www:www app/Plugins/RentalAgreement
find app/Plugins/RentalAgreement -type d -exec chmod 755 {} \;
find app/Plugins/RentalAgreement -type f -exec chmod 644 {} \;

# 3. Migrate
cd /www/wwwroot/admin-homes
php artisan migrate --force --path=app/Plugins/RentalAgreement/database/migrations
php artisan optimize:clear

# 4. Register provider in config/app.php (if not auto-discovered)
# App\Plugins\RentalAgreement\RentalAgreementServiceProvider::class

# 5. Health check
curl -s https://admin-homes.sukoon.group/rental-agreements
```

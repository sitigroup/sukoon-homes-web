# CURSOR COMPOSER PROMPT — Task 17A Foundation
# Paste this entire block into Cursor Composer (Cmd+Shift+I / Ctrl+Shift+I)
# Cursor will read .cursor/rules automatically for project context.

Create the Rental Agreement Engine foundation for Sukoon Homes.
Plugin path: app/Plugins/RentalAgreement/

Create exactly these files with exactly these contents:

---

## 1. app/Plugins/RentalAgreement/RentalAgreementServiceProvider.php

Register the plugin:
- Singleton bindings for: RentalAgreementNumberService, RentalAgreementAuditService, RentalAgreementPdfService
- Bind RentalAgreementProviderInterface to ManualRentalAgreementProvider
- loadViewsFrom __DIR__/views as 'rental-agreement'
- loadMigrationsFrom __DIR__/database/migrations
- loadRoutesFrom __DIR__/routes/web.php

---

## 2. Migrations (in app/Plugins/RentalAgreement/database/migrations/)

### 2026_05_26_000001_create_rental_agreement_sequences_table.php
Columns: id, prefix (string 10, default 'RA'), year (smallint unsigned),
next_number (bigint unsigned, default 1), timestamps.
Unique index on [prefix, year].

### 2026_05_26_000002_create_rental_agreements_table.php
Columns: id, agreement_number (string 20, unique), customer_id (nullable, indexed),
property_id (nullable, indexed), owner_name, owner_phone, owner_email (nullable),
owner_id_type (nullable), owner_id_number (nullable), tenant_name, tenant_phone,
tenant_email (nullable), tenant_id_type (nullable), tenant_id_number (nullable),
property_address (text), city, state, pincode, property_type (default 'Residential'),
monthly_rent (unsigned int), security_deposit (unsigned int),
maintenance_amount (unsigned int, nullable), rent_due_day (tinyint, default 1),
start_date (date), end_date (date), lock_in_months (tinyint, nullable),
notice_period_days (tinyint, default 30),
status (string 30, default 'draft', indexed),
payment_status (string 30, nullable),
pdf_path (string 500, nullable), signed_pdf_path (string 500, nullable),
provider (string 50, default 'manual'),
provider_reference (string 200, nullable), provider_status (string 50, nullable),
provider_payload (json, nullable), admin_notes (text, nullable),
generated_at (timestamp, nullable), completed_at (timestamp, nullable), timestamps.

### 2026_05_26_000003_create_rental_agreement_clauses_table.php
Columns: id, title (string 200), slug (string 200, unique), body (longText),
category (string 80, default 'general'), is_default (boolean, default false, indexed),
is_active (boolean, default true, indexed), sort_order (smallint, default 0), timestamps.

### 2026_05_26_000004_create_rental_agreement_selected_clauses_table.php
Columns: id, agreement_id (foreign → rental_agreements.id, cascade delete),
clause_id (nullable), title (string 200), body (longText), sort_order (smallint, default 0).
No timestamps. Note: body is snapshotted at clause selection time.

### 2026_05_26_000005_create_rental_agreement_audit_logs_table.php
Columns: id, agreement_id (foreign → rental_agreements.id, cascade delete),
customer_id (nullable), admin_id (nullable), action (string 80, indexed),
metadata (json, nullable), created_at (timestamp, useCurrent).
No updated_at.

---

## 3. Models (in app/Plugins/RentalAgreement/Models/)

### RentalAgreementSequence.php
Table: rental_agreement_sequences. Fillable: prefix, year, next_number.

### RentalAgreement.php
Table: rental_agreements.
Status constants: STATUS_DRAFT, STATUS_GENERATED, STATUS_SIGNED_COPY_UPLOADED,
STATUS_COMPLETED, STATUS_CANCELLED.
Reserved v2 constants (define but do not use in v1 UI): STATUS_PENDING_PAYMENT,
STATUS_PAID, STATUS_SENT_FOR_SIGNING, STATUS_SIGNED_BY_OWNER, STATUS_SIGNED_BY_TENANT.
ALLOWED_TRANSITIONS constant array mapping each status to valid next statuses.
Relationships: selectedClauses() hasMany ordered by sort_order, auditLogs() hasMany ordered desc.
Helpers: canTransitionTo(string), isOwnedByCustomer(int), hasPdf(), hasSignedCopy(), formattedRent().
Casts: start_date, end_date as date; generated_at, completed_at as datetime; provider_payload as array.

### RentalAgreementClause.php
Table: rental_agreement_clauses.
Scopes: scopeActive, scopeDefault, scopeOrdered.

### RentalAgreementSelectedClause.php
Table: rental_agreement_selected_clauses. No timestamps.
Relationships: agreement() belongsTo, sourceClause() belongsTo RentalAgreementClause.

### RentalAgreementAuditLog.php
Table: rental_agreement_audit_logs. No timestamps / updated_at only.
Casts: metadata as array, created_at as datetime.

---

## 4. Services (in app/Plugins/RentalAgreement/Services/)

### RentalAgreementNumberService.php
const PREFIX = 'RA', PAD_LENGTH = 6.
generate(int $year = null): string — DB::transaction with lockForUpdate() on sequence row.
Creates row if not exists. Increments next_number. Returns 'RA-2026-000001' format.
peek(int $year = null): string — read-only preview, no DB write.

### RentalAgreementAuditService.php
Action constants: ACTION_CREATED, ACTION_UPDATED, ACTION_PDF_GENERATED,
ACTION_DOWNLOADED, ACTION_SIGNED_COPY_UPLOADED, ACTION_STATUS_CHANGED, ACTION_CANCELLED.
Public methods: logCreated, logUpdated, logPdfGenerated, logDownloaded,
logSignedCopyUploaded, logStatusChanged, logCancelled — each accepting the
appropriate typed params and calling private write().
Private write() wraps in try/catch — audit failure must never break main flow.
Log::error on failure.

### RentalAgreementPdfService.php
PDF library: barryvdh/laravel-dompdf — NOT installed yet (ops approval needed).
generate() throws RuntimeException with clear message about the stub.
renderTokens(string $body, RentalAgreement $agreement): string — replaces all
11 defined tokens: {{tenant_name}} {{owner_name}} {{property_address}} {{monthly_rent}}
{{security_deposit}} {{start_date}} {{end_date}} {{notice_period_days}}
{{lock_in_months}} {{city}} {{agreement_number}}.
Format monthly_rent and security_deposit as ₹XX,XXX. Dates as 'd M Y'.
pdfPath(RentalAgreement): string — 'rental-agreements/pdf/{number}.pdf'
signedCopyPath(RentalAgreement, string $ext): string — 'rental-agreements/signed/{number}-signed.{ext}'

---

## 5. Contracts (in app/Plugins/RentalAgreement/Contracts/)

### RentalAgreementProviderInterface.php
Methods: name(): string, identifier(): string, supports(RentalAgreement): bool,
initiate(RentalAgreement): array, handleCallback(RentalAgreement, array): bool,
supportsCallback(): bool, isLegallyBinding(): bool.
Docblock must list v1=Manual, v2=eStamp/AadhaarESign, v3=DigiLocker.

---

## 6. Providers (in app/Plugins/RentalAgreement/Providers/)

### ManualRentalAgreementProvider.php
Implements RentalAgreementProviderInterface.
Constructor: inject RentalAgreementPdfService, RentalAgreementAuditService.
name(): 'Manual (PDF)', identifier(): 'manual'.
supports(): true when status=draft and provider=manual.
initiate(): calls pdfService->generate() (will throw until library installed),
updates agreement to STATUS_GENERATED, writes logPdfGenerated and logStatusChanged.
Add TODO comment for notification dispatch.
handleCallback(): returns false. supportsCallback(): false. isLegallyBinding(): false.

---

## 7. Http/Controllers/Admin (in app/Plugins/RentalAgreement/Http/Controllers/Admin/)

### RentalAgreementAdminController.php
index(Request $request): query RentalAgreement::query()->latest().
Filters: status (exact match), search (LIKE on agreement_number, owner_name,
tenant_name, city). Paginate 20 with withQueryString().
Stats array: total, draft, generated, completed counts.
Statuses array for filter dropdown: the 5 v1 statuses.
Return view 'rental-agreement::admin.index' with compact.

---

## 8. Routes (in app/Plugins/RentalAgreement/routes/web.php)

Route::prefix('rental-agreements')->name('admin.rental-agreements.')->middleware(['web','auth:admin'])
Routes: GET / → index (name: index), GET /create → index stub (name: create),
GET /{agreement} → index stub (name: show).
Comment: Task 17B will replace stubs with real implementations.

---

## 9. Views (in app/Plugins/RentalAgreement/views/admin/)

### index.blade.php
@extends('layouts.admin'), @section('title', __('Rental Agreements'))
Inline CSS only (no separate CSS file in this task).

Colour rules (enforce strictly):
- Primary buttons: background #1F2937, hover #111827 — NEVER gold on buttons
- Gold #B89A4A: ONLY on agreement number badge (background #F5EED8, border #E4C97E)
  and stat card left accent border — nowhere else
- Border: #D1D5DB | Muted: #6B7280 | Card bg: #FFFFFF

Layout:
- Page header with title + "New Agreement" button (graphite)
- 4 stat cards grid: Total (with gold left border accent), Draft, Generated, Completed
- Filters bar: search input + status select + Filter button + Clear link
- Table: Number (gold badge), Owner/Tenant (two-line), Property/City, Rent, Created date,
  Status pill, View button
- Status pill classes: draft=grey, generated=blue, signed_copy_uploaded=green,
  completed=graphite dark, cancelled=red
- Empty state with icon
- Pagination
- All text strings in __()

---

After creating all files, confirm:
1. No WRTeam core files were touched
2. All PHP files pass `php -l` syntax check
3. Gold is not used on any button or nav element
4. RentalAgreementServiceProvider is in the plugin root

Do not create: seeders, frontend files, notification classes, payment classes,
eSign/eStamp classes, or any file not listed above.

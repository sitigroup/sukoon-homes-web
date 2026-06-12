# Cursor Chat Snippets — Sukoon Rental Agreement
# These are ready-to-paste prompts for common questions.
# Cursor will use .cursor/rules for project context automatically.

---

## If Cursor forgets a rule, prepend this to any prompt:

```
Read .cursor/rules before answering.
Plugin only. No WRTeam core edits.
Gold #B89A4A is accent only — never on buttons.
Primary buttons use #1F2937.
```

---

## Creating a specific service

```
Create RentalAgreementNumberService in app/Plugins/RentalAgreement/Services/.
Use DB::transaction with lockForUpdate() on rental_agreement_sequences.
Format: RA-{YEAR}-{000001}. No max(id)+1.
```

---

## Adding a migration

```
Add a migration to app/Plugins/RentalAgreement/database/migrations/.
Filename prefix: 2026_05_26_XXXXXX.
Additive only — no dropColumn, no change to existing tables.
```

---

## Writing a Blade view

```
Create a Blade view for the rental agreement admin panel.
@extends('layouts.admin').
Inline CSS only.
Primary button: background #1F2937, never gold.
Gold #B89A4A: agreement number badge only.
All strings in __().
```

---

## Asking about status transitions

```
In the RentalAgreement model, what status transitions are allowed?
Use the ALLOWED_TRANSITIONS constant. Check canTransitionTo() before any update.
```

---

## Adding an audit log entry

```
Log this action using RentalAgreementAuditService.
Inject the service. Use the named ACTION_ constant.
Audit failure must never throw — the service handles its own try/catch.
```

---

## Checking if a customer owns an agreement

```
Before allowing this action, verify the customer owns the agreement.
Use $agreement->isOwnedByCustomer(auth()->id()).
Return 403 if not.
```

---

## Asking about the PDF

```
The PDF service is a stub. barryvdh/laravel-dompdf is not yet installed.
Do not call generate() in production until the library is approved.
renderTokens() and path helpers are available and safe to use.
```

---

## Task 17B (next task — do not implement in 17A)

```
This is Task 17B scope. Do not implement until 17A is merged and deployed.
17B includes: wizard frontend, PDF generation, admin show/edit,
secure download, signed copy upload, clause CRUD, My Agreements page.
```

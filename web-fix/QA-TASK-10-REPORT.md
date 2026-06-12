# TASK 10 — Trust Verification E2E QA — Final Sign-off Report

**Date:** 24 May 2026  
**Environment:** Production — `homes.sukoon.group` (web), `admin-homes.sukoon.group` (API/admin)  
**QA customer (browser):** #15 — `hemssarda@gmail.com` / `9990687827`  
**QA customer (API deep-dive):** #19 — `mikisarda1@gmail.com`  
**Primary regression order:** **TV-1SKMYJHK** (id **4**)

---

## Verdict

| Area | Status |
|------|--------|
| Customer web (login, wizard, My Orders, Cashfree) | **PASS** |
| Customer API (create, upload, payment-intent, list, cancel) | **PASS** |
| Backend ops (paid, automation, report, PII purge, rate limits) | **PASS** |
| Admin panel UI (logged-in screens) | **NOT RUN** — needs admin credentials |
| Production blockers | **None** (3 bugs fixed during QA) |

**Recommendation:** Safe to use for real customers on web + API. Complete one manual **admin UI** walkthrough before calling the plugin “fully signed off.”

---

## Executive summary

Trust Verification v1 on Sukoon Homes was tested end-to-end on production: public packages, logged-in tenant wizard (full browser submit), My Orders timeline, Cashfree test payment link, order cancel, and server-side API scripts for upload → paid → automation → report → download → PII delete.

Three production issues were found and fixed: rate-limit middleware fatal error, document upload directory permissions, and login phone UX (India 10-digit field).

---

## Pass / fail matrix

| # | Test | Result | Notes |
|---|------|--------|-------|
| 1 | Customer login (phone + password) | **PASS** | India: fixed **+91** label + 10-digit input (`PhoneLoginForm.jsx`) |
| 2 | Tenant wizard — full browser submit | **PASS** | Order **TV-6HR5CPAU** — POST `/orders` **201**, Step 4 pay, Step 5 success |
| 3 | Tenant wizard — Step 2 mobile | **PASS** | Prefill, clear, re-type 10 digits; no `+91` in field |
| 4 | Owner wizard (Barmer) | **PASS** | Packages load; Owner Standard selectable |
| 5 | My Orders (logged in) | **PASS** | Multiple orders, timeline, Pay / Cancel |
| 6 | Cashfree payment link | **PASS** | Test URL opens ₹1999 for correct order id |
| 7 | Cancel order (UI) | **PASS** | **TV-H0IHINHA** cancelled; actions removed |
| 8 | API create + upload + payment-intent | **PASS** | Orders **TV-I1EGINWV**, **TV-H0IHINHA**, etc. |
| 9 | API manual paid + automation | **PASS** | Automation runs complete on test orders |
| 10 | Report upload + customer download | **PASS** | Order #4 download **200**; CLI uploads need `chown www:www` |
| 11 | PII document purge | **PASS** | Files removed; download **404** after purge |
| 12 | Rate limiting | **PASS** | Post-fix; no `decaySeconds` errors |
| 13 | Guest My Orders gate | **PASS** | “Sign in required” when logged out |
| 14 | Public packages API | **PASS** | Tenant + owner packages for `barmer` |
| 15 | Admin order detail UI | **BLOCKED** | `/trust-verification/orders/{id}` → **302** login (expected) |

---

## Browser E2E (customer #15) — 24 May 2026

| Step | Evidence |
|------|----------|
| Login | Session active; profile in header |
| Tenant wizard | Package → details → review (consent) → submit |
| Order created | **TV-6HR5CPAU** — “Request received” + Step 4 “Pay online” |
| Payment | Cashfree test tab (₹1999); closed without charge |
| Skip pay | Step 5 success + “View my orders” |
| My Orders | **TV-H0IHINHA** (cancelled), **TV-I1EGINWV** (paid via QA script), **TV-6HR5CPAU** (pending) |
| Mobile UX | Clear/edit 10-digit subject phone verified |

---

## API / backend E2E

### Order TV-1SKMYJHK (id 4) — full lifecycle

| Action | Result |
|--------|--------|
| Create + upload + payment-intent | **201** / **200** |
| Admin mark paid | `pending` → `paid` |
| Automation | Run completed |
| Report upload + download | Download **200** (after storage ownership fix) |
| PII purge | Document removed; download **404** |

### Customer #15 test orders (QA data)

| Order | Id | Status (approx.) | Purpose |
|-------|-----|------------------|---------|
| TV-H0IHINHA | 5 | Cancelled (UI) | My Orders + cancel + Cashfree |
| TV-I1EGINWV | 6 | Paid (script) + report (QA) | API full script |
| TV-6HR5CPAU | 7 | Pending (browser) | Full wizard submit |

*You may delete or archive these QA orders in admin when convenient.*

---

## Bugs found and fixed (production)

### 1. Rate limit middleware — **FIXED (blocker)**

- `POST /api/trust-verification/orders` returned **500** (`$decaySeconds` on Laravel 10).
- Fix: `TrustVerificationThrottle.php` — `decayMinutes * 60`, prefixed limiter keys.

### 2. Document upload directory — **FIXED**

- Upload **422** when storage dirs missing / wrong owner.
- Fix: `makeDirectory()` in document service + `chown -R www:www storage/app/trust-verification`.

### 3. Login phone field — **FIXED**

- `react-phone-input-2` mangled Indian numbers on login.
- Fix: India-only **+91** prefix + 10-digit input in `PhoneLoginForm.jsx` (deployed).

### 4. Report download after root CLI upload — **OPS (not code)**

- Reports uploaded via `php` CLI as `root` → customer download **404** until `chown www:www`.
- Web/admin uploads via PHP-FPM are fine; document in runbooks.

### Known minor (deferred)

- `downloadReport` may log `customer_downloaded_report` before 404 check (misleading audit only).
- `documents_required` is **false** in settings — ID front marked `*` in UI but not enforced server-side for submit (by design today).

---

## What still needs you

| Item | Why |
|------|-----|
| **Admin panel login** (email/password) | To verify order detail: consent, documents, audit log, manual paid, report upload UI at `https://admin-homes.sukoon.group/trust-verification/orders/{id}` |
| **Cashfree live payment** (optional) | QA used **test** Cashfree; one real ₹1 test payment optional before go-live |
| **Cleanup QA orders** (optional) | TV-H0IHINHA, TV-I1EGINWV, TV-6HR5CPAU on account #15 — cancel/delete if you want a clean account |

Nothing else is required for customer-facing sign-off.

---

## Deployed / repo files (this task)

| File | Purpose |
|------|---------|
| `plugins/TrustVerification/Http/Middleware/TrustVerificationThrottle.php` | Rate limit fix |
| `plugins/TrustVerification/Services/TrustVerificationDocumentService.php` | mkdir before upload |
| `web-fix/PhoneLoginForm.jsx` → server `src/components/forms/PhoneLoginForm.jsx` | Login 10-digit UX |
| `web-fix/plugins/trust-verification/TrustVerificationPage.jsx` | Wizard mobile prefill/clear |
| `web-fix/test-tv-e2e-api.php` | API smoke |
| `web-fix/test-tv-e2e-full.php` | Full backend E2E |

---

## Server smoke commands

```bash
cd /www/wwwroot/admin-homes
php test-tv-e2e-api.php 19
php test-tv-e2e-full.php 4
php check-tv-report.php 4
php check-tv-download.php 4
# After any root CLI file writes:
chown -R www:www storage/app/trust-verification
```

---

## Sign-off checklist

- [x] Customer can log in with 10-digit mobile
- [x] Customer can submit tenant verification in browser
- [x] Customer sees orders + timeline in My Orders
- [x] Cashfree link generates correctly (test mode)
- [x] Customer can cancel pending order
- [x] API + automation + report + PII flows work
- [ ] Admin UI verified with staff login *(pending your credentials)*
- [ ] Optional: one real Cashfree payment in production

**Prepared by:** E2E QA (Cursor agent) — TASK 10 complete except admin UI.

# Trust Verification — E2E QA Checklist

**Product:** Sukoon Homes background verification (Barmer)  
**Use before/after each deploy.** Tick each row; note order ID and date in the ops log.

**Environments**

| Layer | URL |
|-------|-----|
| Web hub | https://homes.sukoon.group/verification |
| Tenant wizard | https://homes.sukoon.group/tenant-verification-in-barmer |
| Owner wizard | https://homes.sukoon.group/owner-verification-in-barmer |
| My orders | https://homes.sukoon.group/my-verification-orders |
| Admin queue | https://admin-homes.sukoon.group/trust-verification |
| Admin packages | https://admin-homes.sukoon.group/trust-verification/packages |

**Test accounts:** One logged-in web user (customer) + one admin with `read`/`update` on Customers.

---

## A. Public web — discovery & navigation

| # | Step | Expected | Pass |
|---|------|----------|------|
| A1 | Open `/verification` (logged out) | Hub loads; Barmer tenant + owner CTAs visible | ☐ |
| A2 | Header → Pages → Verification | Lands on hub | ☐ |
| A3 | Open rent listing detail (Barmer) | “Verify owner” card visible on standard + custom layout | ☐ |
| A4 | Click verify owner on listing | Owner wizard opens (Barmer) | ☐ |
| A5 | Visit `/tenant-verification/` | 302 → Barmer tenant page | ☐ |
| A6 | Visit `/owner-verification-in-jaipur` | 302 → Barmer owner page | ☐ |

---

## B. Package selection & wizard

| # | Step | Expected | Pass |
|---|------|----------|------|
| B1 | Tenant wizard — package list | Shows active tenant packages (Basic/Standard) with prices | ☐ |
| B2 | Owner wizard — package list | Shows active owner packages | ☐ |
| B3 | Select package → continue | Subject form loads with correct type label | ☐ |
| B4 | Submit with missing required fields | Validation errors; no order created | ☐ |
| B5 | Logged in: hub/wizard shows “My verification orders” link | Link goes to `/my-verification-orders` | ☐ |

---

## C. Order submit — pay now vs pay later

Use a unique test name + phone per run (e.g. `QA Test 2026-05-24`).

| # | Step | Expected | Pass |
|---|------|----------|------|
| C1 | Complete tenant order, choose **Pay later** | Order created; status `submitted`; payment `pending` | ☐ |
| C2 | Check email (requester) | Order submitted mail received | ☐ |
| C3 | Admin queue — filter submitted | New order visible (Barmer only) | ☐ |
| C4 | Complete owner order, choose **Pay now** (Cashfree test) | Cashfree popup opens; on success payment `paid` | ☐ |
| C5 | My orders — unpaid order | Shows Pay + Cancel actions | ☐ |
| C6 | My orders — paid order | No Cancel; Pay hidden | ☐ |

---

## D. User cancel & payment

| # | Step | Expected | Pass |
|---|------|----------|------|
| D1 | My orders → Cancel unpaid `submitted` order | Confirm dialog; status → `cancelled` | ☐ |
| D2 | API: cancel paid order | 422 / error — not allowed | ☐ |
| D3 | API: cancel `in_progress` order | 422 / error — not allowed | ☐ |
| D4 | Pay later order → Pay from my orders | Cashfree flow; payment updates to `paid` | ☐ |
| D5 | Close Cashfree without paying | Order stays `pending`; can retry pay | ☐ |

---

## E. Admin ops — order lifecycle

| # | Step | Expected | Pass |
|---|------|----------|------|
| E1 | Open order detail from queue | Subject, package, checks, payment visible | ☐ |
| E2 | Set status `in_progress` | Saves; reflected on my orders | ☐ |
| E3 | Update check items (pass/fail/pending) | Saves per check | ☐ |
| E4 | Mark payment `waived` (manual) | Updates on order + my orders | ☐ |
| E5 | Upload PDF report + risk + summary | Report saved; status can move to `completed` | ☐ |
| E6 | Report ready email | User receives email with signed download link | ☐ |
| E7 | Admin download report | PDF downloads (auth required) | ☐ |

---

## F. Secure PDF access

| # | Step | Expected | Pass |
|---|------|----------|------|
| F1 | Logged-in user: my orders → Download report | PDF streams (not public URL) | ☐ |
| F2 | Open raw `storage/` or old public path (if known) | 404 or not accessible without auth | ☐ |
| F3 | Email signed link (within 72h) | Download works without login | ☐ |
| F4 | Expired signed link | 403 / invalid signature | ☐ |
| F5 | Run legacy migration dry-run: `php web-fix/migrate-trust-verification-reports.php` | Counts printed; no errors | ☐ |
| F6 | After `--execute` migration | Reports still download via API | ☐ |

---

## G. Admin package CRUD (Phase C)

| # | Step | Expected | Pass |
|---|------|----------|------|
| G1 | Admin → Trust Verification → **Packages** tab | Package list with order counts | ☐ |
| G2 | Edit Standard tenant — change price | Saves; web wizard shows new price | ☐ |
| G3 | Toggle feature included checkbox | Saves; new orders get matching check items | ☐ |
| G4 | Deactivate package (`is_active` off) | Hidden on web; existing orders unchanged | ☐ |
| G5 | Create test package (inactive) | Appears in admin only until activated | ☐ |
| G6 | Sort order | Packages sort correctly on wizard | ☐ |

---

## H. Cashfree sync (observer)

| # | Step | Expected | Pass |
|---|------|----------|------|
| H1 | Simulate webhook updating `payment_transactions` for TV order | Linked `tv_orders.payment_status` → `paid` | ☐ |
| H2 | User refreshes my orders without calling confirm-payment | Payment still shows paid | ☐ |

---

## I. Regression smoke (5 min)

| # | Step | Expected | Pass |
|---|------|----------|------|
| I1 | `GET /api/trust-verification/packages?city=barmer&type=tenant` | JSON list, active only | ☐ |
| I2 | Home page + property search | No console errors from TV plugin | ☐ |
| I3 | Admin sidebar: Users → Trust Verification | Queue loads | ☐ |
| I4 | `php artisan optimize:clear` after deploy | No 500 on admin or API | ☐ |

---

## J. Ops dashboard & cities (Phase C)

| # | Step | Expected | Pass |
|---|------|----------|------|
| J1 | Admin orders index | Stat cards: pending payment, submitted, in progress, overdue, completed 7d | ☐ |
| J2 | Click overdue card | Filters to SLA-past orders only | ☐ |
| J3 | Overdue row highlight | Active orders past package delivery_hours show warning badge | ☐ |
| J4 | Cities tab | Barmer listed as enabled | ☐ |
| J5 | Add city (disabled) | Saves; not on web API until enabled | ☐ |
| J6 | Enable city + add packages | API `/cities` and packages for that slug work | ☐ |
| J7 | Edit package price | Price history table shows old → new entry | ☐ |

---

## K. Multi-city (Phase B)

| # | Step | Expected | Pass |
|---|------|----------|------|
| K1 | Hub `/verification` | Lists Barmer (and other cities with packages) | ☐ |
| K2 | `/tenant-verification-in-barmer` | Barmer tenant wizard (static SEO URL) | ☐ |
| K3 | `/owner-verification-in-{newCity}` | Wizard for enabled city with packages | ☐ |
| K4 | Disabled/unknown city slug | “Not available” + link to hub | ☐ |
| K5 | Rent listing in Barmer | Verify owner card → Barmer owner wizard | ☐ |
| K6 | Rent listing in city without packages | No verify owner card | ☐ |
| K7 | API `GET /cities` | Returns `has_tenant_packages`, `has_owner_packages` | ☐ |

---

## Sign-off

| Field | Value |
|-------|-------|
| Tester | |
| Date | |
| Deploy / git ref | |
| Blockers | |
| Orders used (IDs) | |

**Fail protocol:** Log order number, screenshot, and API response; fix before promoting. For payment issues see status report §4.1 backfill runbook.

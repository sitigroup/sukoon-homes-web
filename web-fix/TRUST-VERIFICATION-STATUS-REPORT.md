# Sukoon Homes — Trust Verification Status Report

> **Superseded for final delivery:** see **[`TRUST-VERIFICATION-FINAL-REPORT.md`](TRUST-VERIFICATION-FINAL-REPORT.md)** (Phases A–D complete, ops runbook, full API/admin reference).

**Product:** NoBroker-style background verification for rentals  
**Brand:** Sukoon Homes (eBroker stack)  
**Scope decision:** Web-only v1, **Barmer-only** (tenant + owner)  
**Report date:** 24 May 2026  
**Environment:** Production on `srv1534644`

**Section index (backend):** 4.1 DB · 4.2 Models · 4.3 Services · 4.4 API · 4.5 Admin · 4.6 Packages · 4.7 Email · 4.8 Lifecycle · **4.9 PDF security** · **4.10 Cashfree confirm**

---

## 1. Executive summary

Trust Verification is a **self-contained Laravel plugin** plus a **Next.js web plugin** that lets users order manual background checks (tenant or owner), pay online or offline, and receive a PDF report by email. Operations staff manage the queue in admin, update individual checks, and upload the final PDF.

**What works today (live):**

- Public web funnel: hub → package selection → subject form → order submit → optional Cashfree pay → my orders + PDF download
- Admin ops queue at `/trust-verification` with check tracking and PDF upload
- Email notifications on submit and when report is ready
- “Verify owner” CTA on rent listing detail pages (standard + custom layout)
- Header menu: **Pages → Verification**

**Current geographic scope:** **Multi-city ready** — enabled cities from admin appear on web/API. Barmer is seeded and enabled by default.

**Not started / deferred:** Hindi UI (explicitly later), Flutter app, multi-city (Phase B). **Phase C** package CRUD + legacy PDF migration script + E2E QA checklist are in repo (May 2026).

---

## 2. Original plan vs delivery

| Planned capability | Status | Notes |
|-------------------|--------|-------|
| Barmer tenant + owner packages | **Done** | 4 packages seeded (Basic ₹999, Standard ₹1999 each) |
| Web order wizard | **Done** | Multi-step: package → subject → review → pay/success |
| Manual PDF report workflow | **Done** | Admin uploads PDF; user downloads from my orders |
| Offline / manual payment | **Done** | Admin marks `paid` or `waived` |
| Cashfree online payment | **Done** | Pay at submit or later from my orders |
| Email on submit + report ready | **Done** | Laravel mailables |
| My verification orders page | **Done** | List, status, pay later, PDF link |
| Admin order queue | **Done** | Filter by status, type, payment |
| Property detail “Verify owner” | **Done** | Standard `PropertyDetails` + custom layout |
| Header navigation entry | **Done** | `/verification` under Pages |
| Multi-city dynamic pages | **Deferred** | Built briefly (v4), reverted to Barmer-only per product decision |
| Flutter mobile app | **Not started** | Out of web v1 scope |
| Admin package/city management UI | **Not started** | Packages via seeder/DB only |
| Automated ID/court checks (API vendors) | **Not started** | Manual ops model for v1 |

---

## 3. Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│  Web (Next.js) — homes.sukoon.group                             │
│  pages/verification, tenant|owner-verification-in-barmer,       │
│  my-verification-orders                                         │
│  src/plugins/trust-verification/*                               │
└───────────────────────────┬─────────────────────────────────────┘
                            │ Sanctum API
┌───────────────────────────▼─────────────────────────────────────┐
│  Admin Laravel — admin-homes.sukoon.group                       │
│  app/Plugins/TrustVerification/                                 │
│    Models, Services, API + Admin controllers, migrations, mail  │
└───────────────────────────┬─────────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────────┐
│  MySQL: tv_packages, tv_orders, tv_subjects,                   │
│         tv_check_items, tv_reports                              │
│  Storage: public/trust-verification/reports/{order_id}/*.pdf    │
└─────────────────────────────────────────────────────────────────┘
```

### Core protection policy

Custom code lives in **plugins** and **documented minimal patches** only (same pattern as AreaListing):

| Hook | File | Purpose |
|------|------|---------|
| API routes | `routes/api.php` | `require` plugin `routes/api.php` |
| Admin routes | `routes/web.php` | `require` plugin `routes/web.php` |
| Admin views | `AppServiceProvider.php` | `loadViewsFrom(... TrustVerification/views)` |

Patch script: `web-fix/patch-trust-verification-routes.php`  
Optional alternative: `web-fix/patch-trust-verification-register.php` (registers `TrustVerificationServiceProvider` in `config/app.php`).

Web stock files with **minimal Sukoon patches** (not in automated web installer):

| File | Change |
|------|--------|
| `web-fix/Header.jsx` | Pages → Verification menu item |
| `web-fix/PropertyDetails.jsx` | `VerifyOwnerPropertyCard` in sidebar |
| `web-fix/plugins/property-detail-switcher/CustomPropertyDetailsPage.jsx` | Same card on custom layout listings |

---

## 4. What is built — backend (Laravel plugin)

**Location:** `plugins/TrustVerification/` → `/www/wwwroot/admin-homes/app/Plugins/TrustVerification/`

### 4.1 Database (migrations)

| Table | Purpose |
|-------|---------|
| `tv_packages` | Sellable plans (type, city_slug, price, features JSON, delivery_hours) |
| `tv_orders` | Customer order (status, payment_status, amount, notes) |
| `tv_subjects` | Person/property being verified (PII, masked ID hint, consent) |
| `tv_check_items` | Per-check pass/fail/pending/na rows |
| `tv_reports` | PDF path, risk_level (green/amber/red), summary |

Migration `2026_05_24_000002` adds `payment_transaction_id` on orders for Cashfree linkage.

**Migration date vs deploy date:** The `2026_05_24_*` prefix is the **repo filename**, not necessarily the day it first ran on production. Both migrations (`000001` tables + `000002` payment column) ship together and were applied when **v2 (Cashfree)** was first deployed. If Cashfree went live the same day as that deploy, there should be **no historical paid orders missing a link column** — backfill is moot unless someone paid before v2 or data was edited manually.

#### Ops: when and how to backfill `payment_transaction_id`

**Step 1 — Check if anything needs action**

Run on admin DB:

```sql
SELECT id, order_number, customer_id, amount, payment_status, payment_transaction_id, created_at
FROM tv_orders
WHERE payment_status IN ('paid')
  AND payment_transaction_id IS NULL;
```

- **Zero rows** → nothing to do; stop here.
- **One or more rows** → reconcile **one order per cycle** (Steps 2–4 below). **Do not batch UPDATE** multiple orders in a single statement. There is no automated backfill script yet.

**Step 2 — Find the Cashfree transaction (source of truth)**

For each affected `tv_orders` row, use **one or more** of:

| Source | Where | What to copy |
|--------|--------|--------------|
| **Cashfree merchant dashboard** | [Cashfree PG](https://merchant.cashfree.com) → **Payment Links** or **Transactions** | **Link ID** (this is Cashfree’s payment-link identifier), amount, paid date, customer phone/email |
| **Settlement / payout report** | Dashboard → **Reports** → settlement or transaction export (CSV) | Link ID, transaction status, settlement date, amount |
| **Customer email/SMS** | Cashfree payment confirmation (if forwarded to ops) | Link ID or payment reference in receipt |

Filter by **order date ± 1 day**, **amount** (`tv_orders.amount`), and **customer** (`requester_phone` / `requester_email` on the order, or profile in admin).

**Step 3 — Match to Laravel `payment_transactions`**

Cashfree **Link ID** is stored in `payment_transactions.order_id` (not `tv_orders.order_number`).

```sql
SELECT id, user_id, amount, order_id, payment_status, transaction_id, created_at
FROM payment_transactions
WHERE payment_gateway = 'Cashfree'
  AND order_id = '<link_id_from_cashfree>';
```

Trust-verification payments typically have `package_id` NULL, `pay_as_you_go_id` NULL, and `payment_type = 'online payment'`. Confirm `user_id` = `tv_orders.customer_id` and `amount` = `tv_orders.amount` before linking.

**Step 4 — Link one order, then verify**

Work on **a single row** from the Step 1 result set (note its `id` and `order_number` before you start):

```sql
UPDATE tv_orders
SET payment_transaction_id = <payment_transactions.id>
WHERE id = <tv_orders.id>
  AND payment_status = 'paid'
  AND payment_transaction_id IS NULL;
```

**Immediately after each UPDATE — re-run the Step 1 check query.**

| Step 1 result after update | Action |
|----------------------------|--------|
| Row count dropped by **exactly 1** and the order you just fixed is **gone** from the list | Safe to continue with the next row (back to Step 2). |
| Row count **unchanged** | STOP — UPDATE did not apply (wrong `id`, already linked, or typo). Fix before continuing. |
| Row count dropped by **more than 1** | STOP — you may have matched the wrong transaction or run a too-broad UPDATE. Investigate; do not proceed until reconciled. |
| Anything else unexpected | STOP — do not batch-fix remaining rows until the last change is understood. |

**Never** link multiple orders in one UPDATE (e.g. `WHERE customer_id = …` or `WHERE amount = …` without a unique `tv_orders.id`). Ambiguous amount/customer matches are how bad backfills happen.

If **no** `payment_transactions` row exists but Cashfree shows **paid**, create/fix the transaction record first (or mark payment via admin **only** after confirming in Cashfree — do not guess IDs). Then re-run Step 1 before moving on.

**Step 5 — If payment was marked paid offline (no Cashfree row)**

Rows with `payment_status = paid` but **no** Cashfree payment were likely set manually in admin (`paid` / `waived`). Leave `payment_transaction_id` **NULL** — that is expected; no backfill required. Re-run Step 1 after skipping these so the list reflects only rows that still need Cashfree linkage.

**Step 6 — Repeat until Step 1 returns zero rows**

Each cycle: Step 1 → pick one row → Steps 2–3 → Step 4 UPDATE → **Step 1 again**. Stop on any anomaly (table above).

**Going forward:** New Cashfree checkouts set `payment_transaction_id` at payment-intent creation; the `PaymentTransactionObserver` (§4.10) syncs `tv_orders.payment_status` when Cashfree’s existing `/webhook/cashfree` updates the transaction.


### 4.2 Models

- `TvPackage`, `TvOrder`, `TvSubject`, `TvCheckItem`, `TvReport`

### 4.3 Services

| Service | Responsibility |
|---------|----------------|
| `TrustVerificationService` | Order creation, check seeding from package features, ID masking, order/package formatting, PDF storage, **Barmer-only city normalization** |
| `TrustVerificationPaymentService` | Cashfree intent via existing `PaymentService`, confirm payment, `payment-settings` payload |
| `TrustVerificationNotificationService` | Triggers emails on submit and report upload |

### 4.4 API endpoints (`/api/trust-verification/…`)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `packages?type=tenant\|owner&city=barmer` | No | Active Barmer packages only |
| GET | `cities` | No | Returns `[{ slug: barmer, label: Barmer }]` only |
| GET | `payment-settings` | No | `{ cashfree_active, offline_fallback }` |
| GET | `orders` | Sanctum | Paginated user orders + report URLs |
| GET | `orders/{id}` | Sanctum | Single order |
| POST | `orders` | Sanctum | Create order + subject + checks |
| POST | `orders/{id}/payment-intent` | Sanctum | Cashfree payment link |
| POST | `orders/{id}/confirm-payment` | Sanctum | Sync payment status after Cashfree popup (client-triggered; see §4.10) |

> **No Cashfree webhook** is implemented. Payment is confirmed only when the browser calls `confirm-payment` after the popup (§4.10, §9 #11).

### 4.5 Admin routes (`/trust-verification/…`)

**Auth:** All routes use middleware `web`, `auth`, `checkLogin` (via `TrustVerificationServiceProvider`). Controllers also call `has_permissions('read'|'update', 'customers')` — same pattern as other eBroker admin modules. Unauthenticated requests redirect to admin login; insufficient permission shows permission error.

| Route | Auth | Action |
|-------|------|--------|
| GET `/` | Admin session + `read customers` | Order list with filters — **Barmer orders only** |
| GET `/orders/{order}` | Admin session + `read customers` | Order detail |
| POST `/orders/{order}/status` | Admin session + `update customers` | Update workflow status |
| POST `/orders/{order}/payment` | Admin session + `update customers` | Mark pending / paid / waived |
| POST `/orders/{order}/checks` | Admin session + `update customers` | Update individual check items |
| POST `/orders/{order}/report` | Admin session + `update customers` | Upload PDF + risk + summary (emails user) |
| GET `/packages` | Admin session + `read customers` | Package list (Barmer) |
| GET `/packages/create` | Admin session + `update customers` | New package form |
| POST `/packages` | Admin session + `update customers` | Create package |
| GET `/packages/{package}/edit` | Admin session + `update customers` | Edit package |
| PUT `/packages/{package}` | Admin session + `update customers` | Update price, features, SLA, active flag |
| GET `/packages` | Admin session + `read customers` | Package list |
| GET `/cities` | Admin session + `read customers` | City list + enable/disable |
| POST `/cities` | Admin session + `update customers` | Add city |
| PUT `/cities/{city}` | Admin session + `update customers` | Update label/sort |
| POST `/cities/{city}/toggle` | Admin session + `update customers` | Enable/disable city |

Admin UI: orders queue with **ops stat cards** + Packages + Cities tabs.

### 4.6 Seeded packages (Barmer)

| Slug | Type | Price | Delivery |
|------|------|-------|----------|
| `tenant-basic-barmer` | tenant | ₹999 | 72h |
| `tenant-standard-barmer` | tenant | ₹1999 | 48h |
| `owner-basic-barmer` | owner | ₹999 | 72h |
| `owner-standard-barmer` | owner | ₹1999 | 48h |

Seeder: `Database/Seeders/TvPackageSeeder.php`

### 4.7 Email

- `TvOrderSubmittedMail` → `views/emails/order-submitted.blade.php`
- `TvReportReadyMail` → `views/emails/report-ready.blade.php`

### 4.8 Order lifecycle

```
submitted → in_progress → completed
                ↓
           cancelled (manual)

payment_status: pending | paid | waived
```

Checks auto-created from package `features` JSON (`included: true/false`). Non-included checks start as `na`.

### 4.9 PDF storage & access (security)

**Current (Phase A deployed):** New reports save to the **`local` disk**. Downloads require:

- Logged-in customer: `GET /api/trust-verification/orders/{id}/report/download` (Sanctum)
- Email: **signed URL** (72h) → `GET /api/trust-verification/reports/{id}/download`
- Admin: `GET /trust-verification/orders/{id}/report/download` (admin session)

API returns `download_available`, not a public `file_url`.

**Legacy:** PDFs uploaded before Phase A may still exist on the **`public` disk**; `reportDiskForPath()` serves from `local` first, then `public` for old files. Migrate with `php web-fix/migrate-trust-verification-reports.php` (dry-run default; `--execute` to copy; optional `--delete-public`).

**Remaining hardening (optional):** Bulk-migrate any remaining reports from `public` to `local` storage and delete obsolete public copies.

### 4.10 Cashfree payment confirmation

| Step | What happens |
|------|----------------|
| 1 | User submits order → `payment_status = pending` |
| 2 | `POST orders/{id}/payment-intent` creates `payment_transactions` row, links `tv_orders.payment_transaction_id` |
| 3 | Cashfree popup opens; user pays |
| 4 | Browser calls `POST orders/{id}/confirm-payment` → `TrustVerificationPaymentService::confirmPayment()` polls Cashfree / reads transaction status |

**Gap (partially mitigated):** There is **no Trust Verification–specific Cashfree webhook**. Payment is confirmed when the browser calls `confirm-payment` **or** when the global `/webhook/cashfree` updates `payment_transactions` and the plugin **`PaymentTransactionObserver`** syncs linked `tv_orders` (deployed Phase A).

**Recovery if still stuck on `pending` after Cashfree shows paid:**

1. Wait a few minutes for `/webhook/cashfree` + observer to sync.
2. User retries “Pay with Cashfree” from my orders.
3. Admin marks payment `paid` at `/trust-verification/orders/{id}` **after** confirming in Cashfree dashboard (see §4.1 backfill steps for Link ID lookup).

**Optional future hardening:** Dedicated TV webhook or nightly reconciliation job (§8.4).

---

## 5. What is built — frontend (Next.js)

**Location:** `web-fix/plugins/trust-verification/` → `homes.sukoon.group/src/plugins/trust-verification/`

### 5.1 Plugin components

| File | Role |
|------|------|
| `TrustVerificationPage.jsx` | Main wizard (tenant/owner), package grid, form, submit, Cashfree step |
| `VerifyOwnerPropertyCard.jsx` | Sidebar CTA on rent listings → owner verification with property prefill |
| `trustVerificationApi.js` | API client |
| `trustVerificationUtils.js` | Barmer constants, INR format, copy — **city always resolves to Barmer** |
| `useTrustVerificationPayment.js` | Cashfree popup + confirm polling |

### 5.2 Pages (live routes)

#### Business intent & URL naming (confirmed)

Paths follow the **NoBroker / industry convention**: the URL names **who is being verified** (the *subject*), **not** who is placing the order (*requester*).

| Path | Subject verified | Who typically orders | Hub card | Property CTA |
|------|------------------|----------------------|----------|--------------|
| `/tenant-verification-in-barmer` | **Tenant** (prospective renter) | **Landlord / owner** checking a renter before lease | “Tenant verification — For landlords” | — |
| `/owner-verification-in-barmer` | **Owner / landlord** | **Tenant / renter** checking landlord before token/rent | “Owner verification — For tenants” | “Verify owner” on listing |

**Common confusion:** “Tenant verification” sounds like “a tenant is verifying something” — but it means “verification **of** a tenant.” Likewise “owner verification” = verification **of** the owner. The labels are **not swapped**; avoid reading the URL from the requester’s perspective.

**Examples:**

- Landlord has a prospective renter → `/tenant-verification-in-barmer` → fill in **tenant’s** name, ID, address.
- Renter viewing a listing → “Verify owner” → `/owner-verification-in-barmer` → fill in **owner’s** details + property address prefilled.

| URL | Source file | Who orders (typical) | Subject checked |
|-----|-------------|----------------------|-------------------|
| `/verification` | `pages-verification-index.jsx` | Either | Hub — pick card above |
| `/tenant-verification-in-barmer` | `pages-tenant-verification-in-barmer.jsx` | Landlord | Tenant |
| `/owner-verification-in-barmer` | `pages-owner-verification-in-barmer.jsx` | Renter | Owner / landlord |
| `/my-verification-orders` | `pages-my-verification-orders.jsx` | Logged-in customer | — |
| `/tenant-verification/` | `pages-tenant-verification-redirect.jsx` | — | **302** → Barmer tenant page |
| `/owner-verification/` | `pages-owner-verification-redirect.jsx` | — | **302** → Barmer owner page |
| `/tenant-verification-in-[citySlug]` | `pages-tenant-verification-in-citySlug.jsx` | — | **302** → Barmer (see below) |
| `/owner-verification-in-[citySlug]` | `pages-owner-verification-in-citySlug.jsx` | — | **302** → Barmer (see below) |

#### `[citySlug]` redirects — HTTP 302 (temporary)

Redirect pages use Next.js `getServerSideProps` with **`permanent: false`** → **HTTP 302**, not 301. This preserves future city-specific SEO when multi-city goes live (§8.2). Do **not** change to `permanent: true` until city pages are real destinations.

### 5.3 User flow (web)

1. Land on hub or tenant/owner page (or property card with prefill).
2. Select Basic or Standard package.
3. Fill subject details + consent checkbox (login required).
4. Submit order → email sent.
5. If Cashfree active: pay now (step 4) or pay later from my orders.
6. Admin processes checks and uploads PDF.
7. User gets report-ready email; downloads PDF from my orders.

### 5.4 UI / branding

- Sukoon black brand tokens (`brandColor`, `brandBg`, etc.) — green primary removed
- Breadcrumbs on all verification pages
- Header: **Pages → Verification** (`verificationServices` label in `en.json` via patch script)

### 5.5 Property detail integration

- **Standard layout:** `web-fix/PropertyDetails.jsx` includes `VerifyOwnerPropertyCard`
- **Custom layout:** `web-fix/plugins/property-detail-switcher/CustomPropertyDetailsPage.jsx`
- Shown only for rent/PG/commercial_rent listings; hidden for own listing
- Always links to `/owner-verification-in-barmer` with `property_id`, address, title query params

---

## 6. Version history (as implemented)

| Phase | Features delivered |
|-------|-------------------|
| **v1** | Plugin + DB, Barmer packages, web wizard, admin queue, manual PDF, offline payment, my orders |
| **v2** | Cashfree checkout, payment-intent/confirm, emails (submit + report), pay later |
| **v3** | Header menu, verify-owner on property detail (both layouts) |
| **v4 (partial, then rolled back)** | Multi-city API + dynamic `[citySlug]` routes — **reverted to Barmer-only** May 2026 |

---

## 7. Deployment & ops

### 7.1 Server paths

| Layer | URL | Path |
|-------|-----|------|
| Admin | https://admin-homes.sukoon.group | `/www/wwwroot/admin-homes` |
| Web | https://homes.sukoon.group | `/www/wwwroot/homes.sukoon.group` |

### 7.2 Deploy scripts

| Script | Purpose |
|--------|---------|
| `scripts/final-sukoon-complete-backup.sh` | Full backup before changes |
| `scripts/deploy-trust-verification-v1.sh` | Backup + admin plugin + web pages |
| `web-fix/install-trust-verification.php` | Migrate + seed packages |
| `web-fix/install-trust-verification-web.php` | Copy web plugin + all verification pages (redirects included). **Does not** deploy `Header.jsx` / property-detail patches — see warning below |
| `web-fix/patch-trust-verification-routes.php` | Minimal route/view hooks |

> **⚠️ Installer gap:** `install-trust-verification-web.php` does **not** copy `Header.jsx`, `PropertyDetails.jsx`, or `CustomPropertyDetailsPage.jsx`. Apply those patches manually after the installer (see `TRUST-VERIFICATION-DEPLOY.md`), then `npm run build`.

### 7.3 Ops — admin queue access (do this now)

The admin queue at **`https://admin-homes.sukoon.group/trust-verification`** is live but **not linked from the admin sidebar** yet. Until a plugin menu item is added (§8.1):

1. **Bookmark** `/trust-verification` for every ops user who processes orders.
2. Add the URL to your internal runbook / WhatsApp ops group.
3. Check the queue at least daily — unprocessed orders have no in-app alert for admins today.

Missing this step is an **operational risk** (orders sit in `submitted` unnoticed).

### 7.4 Post-deploy checklist

```bash
curl -s "https://admin-homes.sukoon.group/api/trust-verification/packages?type=tenant&city=barmer"
curl -sL -o /dev/null -w "%{http_code}\n" "https://homes.sukoon.group/verification"
curl -sL -o /dev/null -w "%{http_code}\n" "https://homes.sukoon.group/tenant-verification-in-barmer"
```

Then: `npm run build` + `pm2 restart homes-sukoon` after web file changes.

### 7.5 Backups taken (May 2026)

- `sukoon-final-complete-20260524-055853`
- `sukoon-final-complete-20260524-061723`
- `sukoon-final-complete-20260524-063211`

### 7.6 Issues fixed during rollout

| Issue | Fix |
|-------|-----|
| 404 after plugin upload | `chown www:www`, dirs `755`, files `644` on plugin folder |
| 500 on packages API | Missing `use TrustVerificationService` in API controller |
| Build fail on PropertyDetails | Restored `ComparePropertyModal` import accidentally removed from custom layout |
| Green UI | Replaced with Sukoon black brand classes |
| `cashfree_active: false` | Use `HelperService::getPaymentDetails('cashfree')` |
| `/tenant-verification/` 404 | Shorthand redirect pages added |
| Hub JSX parse error | Mismatched `</section>` closing tag |

---

## 8. What is pending (detailed roadmap)

### 8.1 Phase A — Product polish (web, Barmer)

| Item | Priority | Description |
|------|----------|-------------|
| **Secure PDF download** | **Done** (Phase A) — optional: migrate legacy `public` files |
| **Cashfree webhook / observer sync** | **Done** (Phase A) — global webhook + `PaymentTransactionObserver` |
| Update `MANIFEST-trust-verification.md` | **Medium** | Align file map with v2–v3 + redirect pages (§8.6) — avoid doc fork on redeploy |
| **Admin sidebar menu entry** | **Done** (Phase A) — Users → Trust Verification |
| **Hindi / i18n copy** | **Deferred** | Barmer market need acknowledged; **not in current build** — pick up in a dedicated i18n pass later |
| Link “My orders” from hub/wizard | **Done** | `MyVerificationOrdersLink` when logged in |
| User order cancellation | **Done** | `POST orders/{id}/cancel` — submitted + unpaid/failed only |
| Refund workflow | Low | Admin refund flag + Cashfree reversal (not in v1) |
| SMS notifications | Low | Parallel to email on submit/report |
| SEO | Low | Structured approach for `/verification` and Barmer landing pages |
| E2E test checklist | **Done** | [`TRUST-VERIFICATION-QA-CHECKLIST.md`](TRUST-VERIFICATION-QA-CHECKLIST.md) |

### 8.2 Phase B — Multi-city expansion

| Item | Status | Description |
|------|--------|-------------|
| Add `tv_packages` rows | Ops | Admin **Packages** + **Cities** |
| City catalog | **Done** | `tv_cities` + admin enable/disable |
| Dynamic city resolution | **Done** | `normalizeCitySlug()`, `resolveCity()`, API `/cities` with package flags |
| City-aware hub | **Done** | `/verification` lists enabled cities from API |
| Property card city | **Done** | `VerifyOwnerPropertyCard` → dynamic city wizard when packages exist |
| Static vs dynamic routes | **Done** | `*-in-barmer` kept; `[citySlug]` pages render wizard |
| Multi-city order submit API | **Done** (May 2026) | `POST /orders` accepts packages for any **enabled** city (was Barmer-only) |

**Note:** Enable a city in admin, seed packages, then it appears on hub and property cards automatically.

### 8.3 Phase C — Admin self-service

| Item | Status | Description |
|------|--------|-------------|
| Package CRUD admin UI | **Done** | `/trust-verification/packages` — create/edit/deactivate without seeder |
| Feature editor | **Done** | Checkbox toggles for `features` JSON (`included` per check) |
| City management | **Done** | `/trust-verification/cities` — add/enable/disable cities (API uses enabled only) |
| Pricing history | **Done** | `tv_package_price_logs` — shown on package edit |
| Ops dashboard | **Done** | Stat cards on orders index + overdue filter/SLA highlight |

### 8.4 Phase D — Automation & integrations

| Item | Status | Description |
|------|--------|-------------|
| Risk scoring rules | **Done** (May 2026) | `TrustVerificationRiskService` — admin order detail shows suggestion; report upload can use **Auto (from checks)** |
| Reconciliation job | **Done** (May 2026) | `php artisan trust-verification:reconcile-payments` (`--dry-run` optional) |
| Third-party verification APIs | **Not started** | IDfy, AuthBridge, etc. — auto-fill check results |
| Document upload (user) | **Not started** | Aadhaar/PAN photos at submit time |
| Dedicated TV webhook | **Not started** | Optional — observer + global Cashfree webhook cover most cases (§4.10) |

### 8.5 Phase E — Flutter app

| Item | Description |
|------|-------------|
| Feature module | `lib/features/trust_verification/` (no fork of stock screens) |
| Screens | Hub, wizard, my orders — reuse same API |
| Cashfree mobile SDK | Platform_type `app` already accepted on payment-intent |
| Deep links | From listing detail → owner verification with prefill |

### 8.6 Documentation / repo hygiene

| Item | Status |
|------|--------|
| `TRUST-VERIFICATION-DEPLOY.md` | **Updated May 2026** — aligned with v2/v3 + Barmer-only |
| `MANIFEST-trust-verification.md` | **Updated May 2026** — see §8.1 if redeploying |
| This status report | **Canonical** — use this for build/pending status |

---

## 9. Known limitations (v1)

1. **Manual ops only** — No automated court/ID API; admin must set each check and upload PDF.
2. **Barmer-only** — **Resolved (Phase B)** — additional cities via admin Cities + Packages.
3. **No admin package UI** — **Resolved (Phase C)** — `/trust-verification/packages`.
4. **No Flutter** — Web-only for customers.
5. **PII handling** — Full ID number not stored; only masked hint. Ops rely on offline collection for full docs.
6. **PDF reports** — New uploads use protected download (§4.9). Legacy public-disk files may still be reachable by old URLs until migrated.
7. **Payment** — Cashfree only for online; no Razorpay/Stripe in this plugin.
8. **Stuck pending payments** — Rare if webhook + observer run; recovery in §4.10. Reconcile Link IDs via §4.1 if `payment_transaction_id` is NULL on paid orders.
9. **Core patches** — Header + property detail files must be re-applied after wrteam web updates if those files are overwritten.
10. **Admin sidebar** — **Trust Verification** under Users menu (Phase A). Bookmark still useful if permissions hide the item.
11. **English-only UI** — Hindi deferred to a later pass (§8.1); not blocking current production use.

---

## 10. File inventory (repo)

### Laravel plugin (`plugins/TrustVerification/`)

```
TrustVerificationServiceProvider.php
routes/api.php, routes/web.php
Http/Controllers/Api/TrustVerificationApiController.php
Http/Controllers/Admin/TrustVerificationAdminController.php
Services/TrustVerificationService.php
Services/TrustVerificationPaymentService.php
Services/TrustVerificationNotificationService.php
Models/TvPackage.php, TvOrder.php, TvSubject.php, TvCheckItem.php, TvReport.php
Mail/TvOrderSubmittedMail.php, TvReportReadyMail.php
database/migrations/2026_05_24_000001_*.php, 2026_05_24_000002_*.php
database/seeders/TvPackageSeeder.php
views/admin/index.blade.php, show.blade.php
views/emails/order-submitted.blade.php, report-ready.blade.php
```

### Web plugin (`web-fix/plugins/trust-verification/`)

```
TrustVerificationPage.jsx
VerifyOwnerPropertyCard.jsx
trustVerificationApi.js
trustVerificationUtils.js
useTrustVerificationPayment.js
```

### Web pages (`web-fix/pages-*.jsx`)

```
pages-verification-index.jsx
pages-tenant-verification-in-barmer.jsx
pages-owner-verification-in-barmer.jsx
pages-my-verification-orders.jsx
pages-tenant-verification-redirect.jsx
pages-owner-verification-redirect.jsx
pages-tenant-verification-in-citySlug.jsx   (redirect → Barmer)
pages-owner-verification-in-citySlug.jsx    (redirect → Barmer)
```

### Patches & deploy (`web-fix/`)

```
patch-trust-verification-routes.php
patch-trust-verification-register.php
install-trust-verification.php
install-trust-verification-web.php
Header.jsx
PropertyDetails.jsx
plugins/property-detail-switcher/CustomPropertyDetailsPage.jsx
patch-en-verification-label.sh / .php
```

---

## 11. How to add a new city (when approved)

1. Insert 4 package rows in `tv_packages` (tenant/owner × basic/standard) with new `city_slug`.
2. Add label to `TrustVerificationService::SUPPORTED_CITIES`.
3. **Backend locks:** Remove or relax `normalizeCitySlug()` and Barmer-only filters in `availableCities()`, `packages()`, admin index query.
4. **Frontend locks (easy to miss):**
   - `trustVerificationUtils.js` — `resolveCity()`, `resolvePropertyCitySlug()` currently always return Barmer; restore dynamic resolution.
   - `VerifyOwnerPropertyCard.jsx` — hardcoded `/owner-verification-in-barmer`; use property city when packages exist.
   - `TrustVerificationPage.jsx` — hardcoded Barmer toggle links; use dynamic `citySlug`.
   - `pages-verification-index.jsx` — restore API city loop (or static multi-city sections).
5. **`[citySlug]` pages:** Replace **302 redirects** with real pages rendering `TrustVerificationPage` (keep `*-in-barmer` static routes for SEO).
6. Deploy plugin + web, rebuild, verify `GET /api/trust-verification/packages?city={slug}` for each city.
7. Update marketing copy; consider Hindi labels per city.

---

## 12. Quick reference URLs (production)

| Who | URL | Notes |
|-----|-----|-------|
| Public hub | https://homes.sukoon.group/verification | |
| Verify **tenant** (landlord orders) | https://homes.sukoon.group/tenant-verification-in-barmer | Subject = tenant |
| Verify **owner** (renter orders) | https://homes.sukoon.group/owner-verification-in-barmer | Subject = owner; property CTA lands here |
| My orders | https://homes.sukoon.group/my-verification-orders | |
| Admin queue | https://admin-homes.sukoon.group/trust-verification | Bookmark — no sidebar yet |
| Packages API | https://admin-homes.sukoon.group/api/trust-verification/packages?type=tenant&city=barmer | |

---

*End of report.*

**Deploy instructions:** see [`TRUST-VERIFICATION-DEPLOY.md`](TRUST-VERIFICATION-DEPLOY.md). File mapping: [`MANIFEST-trust-verification.md`](MANIFEST-trust-verification.md). **Canonical build/pending status: this document.**

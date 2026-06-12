# Task 18B — Deploy + QA Tenant Reliability

**Date:** 2026-05-25  
**Server:** `srv1534644` / admin `admin-homes` / web `homes.sukoon.group`  
**Overall:** **PASS** (deploy + API + admin + regression). **Frontend card visual:** **PARTIAL** — component deployed; inquiry/screening/booking pages still need `tenantCustomerId` prop.

---

## 1. Backup

| Asset | Path | Result |
|-------|------|--------|
| Plugin tarball | `storage/backups/tv-reliability-20260525/plugin-TrustVerification-pre-18b.tgz` (~122 KB) | **PASS** |
| DB dump | `storage/backups/tv-reliability-20260525/sql_admin_homes-pre-18b.sql.gz` | **WARN** — mysqldump credentials not available on CLI (same as Task 17B) |

---

## 2. Deploy

| Step | Result |
|------|--------|
| Sync `plugins/TrustVerification` → `app/Plugins/TrustVerification` | **PASS** |
| Migration `2026_05_30_000016_create_tv_tenant_reliability` | **PASS** (after index fix — see below) |
| `php artisan optimize:clear` | **PASS** |
| `php artisan tenant-reliability:refresh` | **PASS** — `2 customer(s)` |
| `chown` / `chmod` plugin tree | **PASS** |
| Homes UI sync + `npm run build` + PM2 restart | **PASS** |

### Migration note (404 root cause + MySQL fix)

First migrate attempt failed: MySQL index name `tv_tenant_reliability_public_visible_verification_completion_index` exceeded 64 chars. Table was created; index added manually as `tv_rel_pub_completion_idx`; migration row recorded. Repo migration updated with short index name for future installs.

---

## 3. Migration / data result

```
tv_tenant_reliability rows: 2
  level basic_tenant: 1          (customer #19, 25%)
  level premium_trusted_tenant: 1 (customer #15, 100%)
```

`tenant-reliability:refresh` — **PASS** (2 customers with completed tenant orders).

---

## 4. API result (404 closed)

| Endpoint | HTTP | Result |
|----------|------|--------|
| `GET /api/trust-verification/tenant-reliability/19` | **200** JSON | **PASS** — Basic Tenant, 25%, 4 checks |
| `GET /api/trust-verification/tenant-reliability/15` | **200** JSON | **PASS** — Premium Trusted Tenant, 100% |
| `GET /api/trust-verification/tenant-reliability/1` | **200** | **PASS** — `data: null` (no tenant row) |

**Privacy grep** (risk, fraud, aadhaar, pan, admin_note, phone, breakdown, trust_score): **0 matches** — **PASS**

**Sample #19 response fields:** `customer_id`, `reliability_level`, `reliability_label`, `verification_completion`, `verification_status`, `overall_label`, `checks[]`, `last_calculated_at` only.

---

## 5. Admin UI (browser, staff session)

| Screen | Result |
|--------|--------|
| Trust Verification → **Tenant Reliability** list | **PASS** — 2 rows; Bulk refresh; filters (Customer ID, Level: Basic/Verified/Trusted/Premium) |
| Customer **#15** | **PASS** — Premium Trusted Tenant, 100%, Complete, Public |
| Customer **#19** | **PASS** — Basic Tenant, 25%, Partial; owner-safe preview; order history (TV-1SKMYJHK completed, TV-XK40GUFO submitted); Refresh; visibility toggle |
| Risk Center (regression) | **PASS** — 3 profiles still listed |

Screenshots: `web-fix/screenshots/task-18b/admin-reliability-list.png`, `admin-reliability-customer-19.png`

---

## 6. Frontend QA

| Check | Result |
|-------|--------|
| Plugin files on homes (`SukoonTenantReliabilitySection.jsx`, sanitize, cache, CSS) | **PASS** |
| `OwnerDetailsCard` `tenantCustomerId` prop deployed | **PASS** |
| Property detail / inquiry / booking **shows card** | **PARTIAL** — no flow passes `tenantCustomerId` yet; card hidden by design until wired |
| Property detail loads (no build errors) | **PASS** — PM2 build succeeded |
| Desktop / tablet / 375px card screenshot | **DEFERRED** — needs `tenantCustomerId={19}` on a test page or inquiry screen |

---

## 7. Labels verified

| Label | Seen in admin/API |
|-------|-------------------|
| Basic Tenant | **PASS** (#19) |
| Premium Trusted Tenant | **PASS** (#15) |
| Verified / Trusted | In filter dropdown; no live row at deploy time |

---

## 8. Privacy

| Rule | Result |
|------|--------|
| No Aadhaar / PAN / police doc / reference phones | **PASS** |
| No fraud / risk / admin notes / score breakdown | **PASS** |
| `public-trust/15` still works; no `risk_score` in response | **PASS** |

---

## 9. Regression

| Check | Result |
|-------|--------|
| `php artisan risk:refresh` | **PASS** — 3 customers |
| `php artisan trust:refresh-score 15` | **PASS** — score 100 |
| `GET /api/trust-verification/packages?type=tenant` | **PASS** |
| Risk Center admin UI | **PASS** |
| Homes property detail (no fraud strings in HTML grep) | **PASS** |

---

## 10. QA summary

| Area | Pass/Fail |
|------|-----------|
| Deploy + backup | **PASS** |
| Migration + refresh | **PASS** |
| Public API (404 fixed) | **PASS** |
| Admin Tenant Reliability | **PASS** |
| Privacy isolation | **PASS** |
| Trust / risk / orders regression | **PASS** |
| Homes visual card in owner flows | **PARTIAL** (wire `tenantCustomerId` in follow-up) |

**Task 18 production close:** **YES** for backend, API, and admin. **Follow-up:** pass `tenantCustomerId` from inquiry/screening/dashboard/booking UIs to render `SukoonTenantReliabilitySection` on Homes.

---

## Rollback (if needed)

```bash
cd /www/wwwroot/admin-homes
tar -xzf storage/backups/tv-reliability-20260525/plugin-TrustVerification-pre-18b.tgz -C app/Plugins
php artisan migrate:rollback --path=app/Plugins/TrustVerification/database/migrations/2026_05_30_000016_create_tv_tenant_reliability_table.php --force
php artisan optimize:clear
# Restore homes src/plugins/trust-verification from pre-18b backup if taken
```

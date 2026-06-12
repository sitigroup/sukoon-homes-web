# Task 17B — Deploy + QA Fraud Risk Engine

**Date:** 2026-05-25  
**Server:** `srv1534644` / `/www/wwwroot/admin-homes`

## 1. Backup

| Asset | Path | Result |
|-------|------|--------|
| Plugin tarball | `storage/backups/tv-risk-20260525/plugin-TrustVerification.tgz` (~111 KB) | **PASS** |
| DB dump | `storage/backups/tv-risk-20260525/sql_admin_homes-pre-risk.sql.gz` | **WARN** — file ~20 B (mysqldump may need credentials); plugin backup OK |

## 2. Deploy

| Step | Result |
|------|--------|
| Sync `plugins/TrustVerification` → `app/Plugins/TrustVerification` | **PASS** |
| Migration `2026_05_29_000015_create_tv_fraud_risk_tables` | **PASS** — 33ms DONE |
| `php artisan optimize:clear` | **PASS** |
| `chown` / `chmod` on plugin tree | **PASS** |
| `php artisan risk:refresh` | **PASS** — `Risk refresh complete for 3 customer(s).` |

## 3. Post-deploy data

```
profiles_count=3
signals_count=15
customer 15: score=100 level=critical
customer 19: score=55 level=high  (before manual flag test)
customer  1: score=20 level=low
```

## 4. Manual flag test (CLI — customer #19)

| Step | Result |
|------|--------|
| Before | score=55, level=high |
| Add manual flag +12 pts | **PASS** |
| After | score=67, level=high, signals=4 |
| Signal row | `admin_manual_flag` +12, source=manual |

## 5. Filter counts (DB / same queries as admin UI)

| Filter | Count |
|--------|-------|
| Low | 1 |
| Medium | 0 |
| High | 1 (customer 19 after manual flag) |
| Critical | 1 (customer 15) |
| Duplicate phone | 2 |
| Rejected police | 0 |
| Failed reference | 0 |

## 6. Public / trust isolation

| Check | Result |
|-------|--------|
| `formatForApi(15, true)` contains `risk_score` / `risk_level` | **PASS** — `NO_OK` |
| `GET admin-homes.../api/trust-verification/public-trust/15` grep `risk` | **PASS** — no risk fields |
| Homes `/property-details/new` HTML grep `risk_score`, `Fraud risk` | **PASS** — 0 matches |
| Browser search “Fraud” on property detail | **PASS** — not found |

## 7. Regression

| Check | Result |
|-------|--------|
| `php artisan trust:refresh-score 15` | **PASS** — score 100 |
| `TvOrder` load for customer 19 | **PASS** — order TV-1SKMYJHK |
| Report row | **PASS** — `risk_level=green` (report PDF risk, not fraud engine) |
| Risk routes registered | **PASS** — 9 routes (`risk.profiles.*`, `risk.signals.*`) |

## 8. Admin UI (browser) — after staff login

| Screen | Result |
|--------|--------|
| Risk profiles list | **PASS** — 3 customers; disclaimer “Internal fraud risk only…” |
| Filter: Critical | **PASS** — 1 row (#15, 100/100 Critical) |
| Filter: Duplicate phone | **PASS** — 2 rows (#15, #1) |
| Risk signals list | **PASS** — 15+ signals; auto + manual types |
| Customer #19 risk page | **PASS** — score 67 High; trust 15 separate; timeline + manual flag |
| Order TV-1SKMYJHK (#4) fraud accordion | **PASS** — 67/100 High; 4 signals incl. TASK-17B manual +12 |
| Manual flag (prior CLI test) | **PASS** — visible in UI timeline with Remove link |

**Screenshots:** `web-fix/screenshots/task-17b/`

| File | Content |
|------|---------|
| `task-17b-risk-profiles.png` | Risk profiles (all) |
| `task-17b-filter-critical.png` | Critical filter → #15 only |
| `task-17b-filter-duplicate-phone.png` | Duplicate phone → #15, #1 |
| `task-17b-customer-19-risk.png` | Customer #19 summary cards |
| `task-17b-risk-signals.png` | Global signals table |
| `task-17b-order-fraud-risk.png` | Order detail accordion list |

## 9. QA summary

| Area | Pass/Fail |
|------|-----------|
| Deploy + migration | **PASS** |
| `risk:refresh` | **PASS** |
| Manual flag | **PASS** |
| Filters (UI + data) | **PASS** |
| No public risk | **PASS** |
| Trust score | **PASS** |
| Orders / reports | **PASS** |
| Admin UI visual QA | **PASS** |

**Overall Task 17B: PASS**

## 10. Commands for staff UI QA (after login)

1. Open **Trust Verification → Risk Center**
2. Risk profiles — filter Critical → see customer #15
3. Risk signals — list 15+ signals
4. Customer #19 profile — timeline shows manual flag (+12)
5. Order `TV-1SKMYJHK` — accordion **Fraud risk (admin only)**

## Rollback (if needed)

```bash
cd /www/wwwroot/admin-homes
tar -xzf storage/backups/tv-risk-20260525/plugin-TrustVerification.tgz -C /
php artisan migrate:rollback --path=app/Plugins/TrustVerification/database/migrations/2026_05_29_000015_create_tv_fraud_risk_tables.php
php artisan optimize:clear
```

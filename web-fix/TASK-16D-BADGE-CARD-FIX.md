# BUG FIX — Property card Trusted Owner / Trusted Tenant

## Root cause

| Layer | Issue |
|-------|--------|
| **Backend** | `public-trust` returned catalog badges including `trusted_tenant` (auto-assigned when tenant order trust score ≥ 80). |
| **Frontend (production)** | Card logic likely read `badges[]` slug `trusted_tenant` or defaulted to tenant label when any trust data existed. |
| **Component** | `PropertyCardTrustLabel` (used by `PropertyVerticalCard`, `PropertyHorizontalCard` — search, map, featured, related all reuse these). |

## Fix

### API `GET /api/trust-verification/public-trust/{id}`

**Before (problem):**
```json
{
  "trust_score": 72,
  "badges": [{ "slug": "trusted_tenant", "name": "Trusted Tenant" }],
  "sukoon_verified_owner": null
}
```
Cards showed **Trusted Tenant** for almost every owner with a tenant verification score.

**After:**
```json
{
  "owner_trust_badge": true,
  "tenant_trust_badge": false,
  "sukoon_verified_owner": { "title": "Sukoon Verified Owner", "badge_number": "SVO-2026-000001" },
  "badges": []
}
```

`owner_trust_badge` = active **SVO** issued badge (verified, not expired/revoked).  
`tenant_trust_badge` = completed tenant order + trusted reliability tier + active **SVT** (profile / My Orders only).

Catalog slugs `trusted_owner` / `trusted_tenant` are **removed** from public `badges[]` so nothing else can misread them.

### Card UI (public listings only)

```javascript
if (owner_trust_badge) → "Trusted Owner"
else → null  // tenant_trust_badge is never used on property cards
```

### Backfill (issued badges were empty)

```bash
php artisan trust-verification:issue-missing-badges --dry-run
php artisan trust-verification:issue-missing-badges --admin-verify
```

Admin order panel: **Issue badge now** + **Issue as verified (admin override)**.

## Surfaces checked

| Surface | Component |
|---------|-----------|
| Search / grid | `PropertyVerticalCard` |
| Horizontal / list | `PropertyHorizontalCard` |
| Map / featured / related | Same cards (wrteam listing components) |

## Deploy

```bash
# Admin plugin
rsync plugins/TrustVerification → admin-homes/app/Plugins/TrustVerification/
php artisan optimize:clear

# Homes frontend
rsync web-fix/plugins/trust-verification → homes/src/plugins/trust-verification/
npm run build && pm2 restart homes-sukoon
```

## QA

| # | Case | Expected |
|---|------|----------|
| 1 | Owner with SVO verified | Card: **Trusted Owner** |
| 2 | Owner with only tenant SVT (no SVO) | Card: **Trusted Tenant** |
| 3 | Owner with neither | **No badge** |
| 4 | Revoked SVO | **No badge** |
| 5 | API | `owner_trust_badge` / `tenant_trust_badge` match UI |

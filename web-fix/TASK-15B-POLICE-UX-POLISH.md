# Task 15B — Police Verification UX Polish

**Scope:** Plugin views/CSS only. No DB, migration, or API changes.

## Changes

| # | Requirement | Implementation |
|---|-------------|----------------|
| 1 | Hide API-ready complexity | **Advanced options** `<details>` — Police Provider Reference + Status check URL |
| 2 | Rajasthan status | Top-right **Check Rajasthan status** → new tab (official URL) |
| 3 | Acknowledgement UI | Premium **upload cards** with icon, empty state, replace/upload button |
| 4 | Timeline UX | Visual stepper: Submitted → Under Review → (Need Docs) → Completed |
| 5 | Auto dates | Server already stamps on quick status (existing). Form JS fills `reviewed_at` / `completed_at` when empty on status change or quick submit |
| 6 | Empty states | Reference: **Not submitted** · Mobile: **Not available** (muted italic) |
| 7–8 | Mobile + spacing | Responsive timeline, full-width buttons at 375px, card padding |

## Files changed

- `views/admin/partials/police-verification-panel.blade.php`
- `views/admin/partials/tv-order-detail-theme.blade.php`

## Deploy

```bash
rsync -a plugins/TrustVerification/views/ srv:/www/wwwroot/admin-homes/app/Plugins/TrustVerification/views/
cd /www/wwwroot/admin-homes && php artisan optimize:clear
```

## QA (live — order TV-1SKMYJHK / #4, 2026-05-25)

| Check | Result |
|-------|--------|
| Police accordion + helper | **PASS** |
| Empty states (reference / mobile) | **PASS** — *Not submitted*, *Not available* |
| Timeline + quick actions | **PASS** |
| **Under Review** quick action | **PASS** — status *Under review*; `reviewed_at` auto `2026-05-25T14:29` |
| Success flash | **PASS** — “Police verification status updated.” |
| Criminal check note | **PASS** — “Rajasthan Police verification under_review.” |
| Check Rajasthan status link | **PASS** — new tab |
| Advanced → Police Provider Reference | **PASS** (no “API-ready” in main form) |
| Acknowledgement / certificate cards | **PASS** |

Screenshots: `web-fix/screenshots/task-15b/police-panel-order-4.png`, `police-under-review-order-4.png`

**Overall: PASS**

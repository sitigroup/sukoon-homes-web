# TASK 16E — Trusted Owner Badge Production Activation

## Owner → SVO flow

1. Customer completes **owner** verification order (`order_type=owner`, `status=completed`, `payment_status` paid/waived).
2. All package checks pass (documents, checks, reference/police when enabled).
3. `TrustVerificationIssuedBadgeService::tryAutoIssue()` on `order_completed`, or admin **Issue Owner Badge**, or CLI backfill.
4. Badge row: `badge_type=owner`, `badge_number=SVO-YYYY-######`, `status=verified`.
5. Public API sets `owner_trust_badge=true`, `tenant_trust_badge=false`, `sukoon_verified_owner={...}`.
6. Property cards show **Trusted Owner** (never tenant on listings).

## Duplicate prevention

One active verified **SVO** per customer. Second owner order cannot issue until the first is revoked/expired.

## Admin (owner orders only)

- **Issue Owner Badge** — eligible → verified; else pending.
- **Issue Verified Owner Badge (Admin Override)** — verified immediately.

## CLI

```bash
# Backfill all missing badges (owner + tenant)
php artisan trust-verification:issue-missing-badges --dry-run
php artisan trust-verification:issue-missing-badges --admin-verify

# Owner orders only (SVO output format)
php artisan trust-verification:issue-missing-badges --owners-only --admin-verify

# Activate lister with tenant-only history (creates completed owner order from template)
php artisan trust-verification:activate-owner-svo 15 --from-order=17
```

## QA customer 15

Properties: 13, 14, 15, 18, 19, 21, 22 (`added_by=15`).

After `activate-owner-svo 15`:

- API: `owner_trust_badge: true`, `tenant_trust_badge: false`
- Cards: **Trusted Owner**

# TASK — Trust Verification Order Number Series Settings

## Settings keys (`tv_settings`)

| Key | Default | Description |
|-----|---------|-------------|
| `order_number_prefix` | `TV` | Uppercase letters/numbers, max 8 |
| `order_number_format` | `random` | `random`, `sequential`, `year_sequence`, `city_sequence` |
| `order_number_next_sequence` | `1` | Next number for sequential formats (transaction-locked) |
| `order_number_digits` | `6` | Zero-pad length (4–12) |
| `order_number_separator` | `-` | Single character between segments |

## Format examples

| Format | Example |
|--------|---------|
| Random | `TV-ABCDEFGH` |
| Sequential | `SHV-000001` |
| Year + sequence | `SHV-2026-000002` |
| City + sequence | `SHV-BMR-000003` (Barmer → BMR via consonant code) |

## Changed files

| File |
|------|
| `Services/TrustVerificationOrderNumberService.php` *(new)* |
| `Services/TrustVerificationSettingsService.php` |
| `Services/TrustVerificationService.php` |
| `Services/TrustVerificationAuditLogService.php` |
| `Http/Controllers/Admin/TrustVerificationAutomationAdminController.php` |
| `views/admin/automation/index.blade.php` |
| `database/migrations/2026_05_26_000012_seed_tv_order_number_settings.php` |

## Admin UI

**Trust Verification → Automation** — “Order number series” section with live preview.

Permission: `trust_verification_settings` (ROLE_SETTINGS).

## Audit

Action: `order_series_settings_updated` — metadata includes `before` / `after` payloads.

## Test plan

1. Run migration: `php artisan migrate`
2. Open Automation settings — confirm preview updates live
3. Set prefix `SHV`, format `sequential`, next sequence `1` → create order → `SHV-000001`
4. Change format to `year_sequence`, next sequence `2` → create order → `SHV-2026-000002`
5. Confirm existing `TV-*` orders unchanged

## Rollback

```bash
php artisan migrate:rollback --step=1   # removes seeded keys only
```

To restore random-only behaviour without rollback, set format back to `random` and prefix `TV` in admin.

Existing orders are never modified by settings changes.

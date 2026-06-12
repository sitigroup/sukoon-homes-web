# TASK 7 — Cashfree webhook security (Trust Verification)

## Migration

`2026_05_24_000008_create_tv_payment_webhook_events_table.php`

```bash
cd /www/wwwroot/admin-homes
php artisan migrate --force --path=app/Plugins/TrustVerification/database/migrations/2026_05_24_000008_create_tv_payment_webhook_events_table.php
php artisan optimize:clear
```

## Signature secret configuration

Uses the **same secret as Cashfree PG API** (no separate env key):

- Admin → Payment settings → **Cashfree secret key** (`cashfree_secret_key` via `HelperService::getPaymentDetails('cashfree')`)

Cashfree signs webhooks with: `base64(HMAC-SHA256(timestamp + raw_body, secret))`

Headers required:

- `x-webhook-signature`
- `x-webhook-timestamp`

**TV payment links** created after deploy use notify URL:

`POST /api/trust-verification/webhook/cashfree`

(see `web-fix/CashfreePayment.php` when `link_notes.trust_verification_order_id` is set)

Older links may still hit `/webhook/cashfree` (core); the plugin **observer** applies paid-lock, amount checks, and replay hashing on sync.

## Endpoints

| Route | Purpose |
|-------|---------|
| `POST /api/trust-verification/webhook/cashfree` | Strict signature + replay table + TV-only processing |
| `POST /webhook/cashfree` | Core (unchanged); TV orders still sync via observer |

## Test commands

Replace `SECRET`, `LINK_ID`, and paths. Run on server (`admin-homes`).

### Generate signature (bash)

```bash
SECRET='your_cashfree_secret_key'
TS=$(date +%s)
PAYLOAD='{"type":"PAYMENT_SUCCESS_WEBHOOK","data":{"link_id":"link_123"}}'
SIG=$(printf '%s' "${TS}${PAYLOAD}" | openssl dgst -sha256 -hmac "$SECRET" -binary | base64 -w0)
echo "timestamp=$TS"
echo "signature=$SIG"
```

### 1) Valid webhook (expect 200, order → paid if txn exists)

```bash
curl -sS -X POST "https://admin-homes.sukoon.group/api/trust-verification/webhook/cashfree" \
  -H "Content-Type: application/json" \
  -H "x-webhook-timestamp: $TS" \
  -H "x-webhook-signature: $SIG" \
  -d "$PAYLOAD"
```

### 2) Invalid signature (expect 403)

```bash
curl -sS -X POST "https://admin-homes.sukoon.group/api/trust-verification/webhook/cashfree" \
  -H "Content-Type: application/json" \
  -H "x-webhook-timestamp: $TS" \
  -H "x-webhook-signature: invalid" \
  -d "$PAYLOAD"
```

### 3) Duplicate webhook (expect 200, `duplicate: true`, no second status change)

Re-run the **same** curl as (1) with identical body and headers.

### 4) Amount mismatch (expect 422 after ingest; order stays unpaid)

Use a `payment_transactions` row whose `amount` ≠ `tv_orders.amount`, then send success webhook for its `order_id` (link_id).

### 5) Already-paid downgrade blocked (expect 200; order stays paid)

Send `PAYMENT_FAILED_WEBHOOK` for an order already `payment_status=paid`. Check `tv_audit_logs` for `payment_webhook_downgrade_blocked`.

### Reconcile (must not downgrade paid)

```bash
php artisan trust-verification:reconcile-payments
php artisan trust-verification:reconcile-payments --dry-run
```

## Rollback

```bash
php artisan migrate:rollback --path=app/Plugins/TrustVerification/database/migrations/2026_05_24_000008_create_tv_payment_webhook_events_table.php
# Revert plugin files + CashfreePayment notify_url patch from git/backup
php artisan optimize:clear
```

Paid orders and manual admin payment changes are unaffected by rollback of the **table** only; revert code together if removing TASK 7 logic.

## Changed files (plugin)

- `database/migrations/2026_05_24_000008_create_tv_payment_webhook_events_table.php`
- `Models/TvPaymentWebhookEvent.php`
- `Services/TrustVerificationCashfreeWebhookService.php`
- `Services/WebhookSecurityException.php`
- `Services/TrustVerificationPaymentService.php`
- `Services/TrustVerificationAuditLogService.php`
- `Observers/PaymentTransactionObserver.php`
- `Http/Controllers/Api/TrustVerificationCashfreeWebhookController.php`
- `routes/api.php`
- `web-fix/CashfreePayment.php` (notify URL for TV links — patch core `app/Services/Payment/CashfreePayment.php`)

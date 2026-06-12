# TASK 9 — Trust Verification API rate limiting

## Limiter names and exact limits

| Limiter name | Scope | Limit |
|--------------|-------|-------|
| `tv-order-create-user` | Authenticated customer | **5 / hour** |
| `tv-order-create-ip` | Client IP | **10 / hour** |
| `tv-document-upload-order` | Order ID | **10 / hour** |
| `tv-document-upload-user` | Customer | **20 / hour** |
| `tv-document-upload-ip` | Client IP | **30 / hour** |
| `tv-payment-intent-order` | Order ID | **5 / hour** |
| `tv-payment-intent-user` | Customer | **10 / hour** |
| `tv-confirm-payment-order` | Order ID | **10 / hour** |
| `tv-webhook-automation-ip` | Client IP | **60 / minute** |
| `tv-webhook-cashfree-ip` | Client IP | **120 / minute** |

## Middleware profiles → routes

| Profile | Route |
|---------|--------|
| `order-create` | `POST …/orders` |
| `document-upload` | `POST …/orders/{order}/documents` |
| `payment-intent` | `POST …/orders/{order}/payment-intent` |
| `confirm-payment` | `POST …/orders/{order}/confirm-payment` |
| `webhook-automation` | `POST …/webhook/automation` |
| `webhook-cashfree` | `POST …/webhook/cashfree` |

429 response:

```json
{
  "error": true,
  "message": "Too many attempts. Please try again later.",
  "retry_after": 3600
}
```

Header: `Retry-After: {seconds}`

Audit: `rate_limit_exceeded` (route, customer_id, order_id, IP, profile, retry_after)

## Changed files

- `Services/TrustVerificationRateLimiterRegistrar.php` (new)
- `Http/Middleware/TrustVerificationThrottle.php` (new)
- `TrustVerificationServiceProvider.php`
- `routes/api.php`
- `Services/TrustVerificationAuditLogService.php`

## Deploy

```bash
cd /www/wwwroot/admin-homes
php artisan optimize:clear
```

## Test commands

```bash
API="https://admin-homes.sukoon.group/api/trust-verification"
TOKEN="your_sanctum_token"
```

### Order creation (6th request within hour should 429)

```bash
for i in 1 2 3 4 5 6; do
  curl -sS -o /tmp/tv-rate.json -w "HTTP %{http_code}\n" -X POST "$API/orders" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"package_id":1,"order_type":"tenant","city_slug":"barmer","subject":{"full_name":"Test","phone":"9999999999","consent_given":true}}'
  cat /tmp/tv-rate.json; echo
done
```

### Document upload flood

```bash
for i in $(seq 1 12); do
  curl -sS -w "HTTP %{http_code}\n" -X POST "$API/orders/ORDER_ID/documents" \
    -H "Authorization: Bearer $TOKEN" \
    -F "doc_type=id_front" \
    -F "file=@small.jpg"
done
```

### Webhook (high volume — normal retries under 120/min should pass)

```bash
for i in $(seq 1 5); do
  curl -sS -w "HTTP %{http_code}\n" -X POST "$API/webhook/cashfree" \
    -H "Content-Type: application/json" -d '{}'
done
```

Check audit:

```sql
SELECT * FROM tv_audit_logs WHERE action = 'rate_limit_exceeded' ORDER BY id DESC LIMIT 10;
```

## Rollback

Restore previous versions of the five plugin files, then:

```bash
php artisan optimize:clear
```

No migration required.

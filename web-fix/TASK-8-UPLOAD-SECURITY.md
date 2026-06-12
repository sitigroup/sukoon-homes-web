# TASK 8 — Trust Verification upload security

## Validation rules

| Rule | Value |
|------|--------|
| Max size | 5 MB (5,242,880 bytes) |
| Extensions | `jpg`, `jpeg`, `png`, `webp`, `pdf` only (final segment) |
| MIME (finfo) | `image/jpeg`, `image/png`, `image/webp`, `application/pdf` |
| Blocked | Double extensions (e.g. `file.jpg.exe`), any blocked segment (`exe`, `php`, `js`, `html`, `svg`, `zip`, `doc`, …) |
| Images | Must decode; re-encoded to safe JPEG on store (when GD available) |
| PDF | Must start with `%PDF`; rejects `/Encrypt` and `/JavaScript` in first 128 KB |
| Storage name | `tv_order_{orderId}_{docType}_{YmdHis}_{random10}.{ext}` — never uses client path |
| Display name | Sanitized original name only (admin + API `original_name`) |

## Changed files

- `Services/TrustVerificationDocumentUploadValidator.php` (new)
- `Services/DocumentUploadRejectedException.php` (new)
- `Services/TrustVerificationDocumentService.php`
- `Services/TrustVerificationAuditLogService.php`
- `Http/Controllers/Api/TrustVerificationApiController.php`
- `Http/Controllers/Admin/TrustVerificationAdminController.php`
- `views/admin/partials/order-documents-automation.blade.php`

## Audit actions

- `customer_upload_accepted`
- `customer_upload_rejected`
- `admin_document_download_blocked`

## Deploy

```bash
# Copy plugin files to admin-homes/app/Plugins/TrustVerification/
cd /www/wwwroot/admin-homes
php artisan optimize:clear
php -l app/Plugins/TrustVerification/Services/TrustVerificationDocumentUploadValidator.php
```

## Test commands

Requires auth token and an order in `submitted` / `in_progress`.

```bash
API="https://admin-homes.sukoon.group/api/trust-verification"
TOKEN="your_sanctum_token"
ORDER_ID=1
```

### Valid JPG

```bash
curl -sS -X POST "$API/orders/$ORDER_ID/documents" \
  -H "Authorization: Bearer $TOKEN" \
  -F "doc_type=id_front" \
  -F "file=@/path/to/valid.jpg;type=image/jpeg"
```

### Valid PDF

```bash
curl -sS -X POST "$API/orders/$ORDER_ID/documents" \
  -H "Authorization: Bearer $TOKEN" \
  -F "doc_type=address_proof" \
  -F "file=@/path/to/valid.pdf;type=application/pdf"
```

### `.jpg.exe` rejected

```bash
curl -sS -X POST "$API/orders/$ORDER_ID/documents" \
  -H "Authorization: Bearer $TOKEN" \
  -F "doc_type=id_front" \
  -F "file=@evil.jpg.exe"
```

### Fake PDF (text file renamed)

```bash
echo "not a pdf" > /tmp/fake.pdf
curl -sS -X POST "$API/orders/$ORDER_ID/documents" \
  -H "Authorization: Bearer $TOKEN" \
  -F "doc_type=id_front" \
  -F "file=@/tmp/fake.pdf;type=application/pdf"
```

### Oversized file (>5 MB)

```bash
# Linux: dd if=/dev/zero of=/tmp/big.jpg bs=1M count=6
curl -sS -X POST "$API/orders/$ORDER_ID/documents" \
  -H "Authorization: Bearer $TOKEN" \
  -F "doc_type=id_front" \
  -F "file=@/tmp/big.jpg"
```

### Missing file download (404)

Use admin or customer download URL for a document row whose file was purged (`deleted_at` set / file removed). Expect **404** and admin audit `admin_document_download_blocked`.

```bash
curl -sS -o /dev/null -w "%{http_code}\n" \
  "$API/orders/$ORDER_ID/documents/$DOC_ID/download" \
  -H "Authorization: Bearer $TOKEN"
```

## Rollback

Restore previous plugin copies of the files listed above from backup, then:

```bash
php artisan optimize:clear
```

Existing stored paths under `trust-verification/documents/` remain valid; no migration required.

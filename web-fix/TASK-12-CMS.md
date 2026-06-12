# TASK 12 — Trust Verification Content CMS

## Migration

`2026_05_24_000009_create_tv_content_blocks_table.php`

Table: `tv_content_blocks` (`content_key`, `group_key`, `title`, `type`, `content`, `content_json`, `is_active`, `sort_order`, `version`, `updated_by`, timestamps)

## Deploy

```bash
cd /www/wwwroot/admin-homes
php artisan migrate --path=app/Plugins/TrustVerification/database/migrations/2026_05_24_000009_create_tv_content_blocks_table.php
php artisan db:seed --class="App\\Plugins\\TrustVerification\\Database\\Seeders\\TvContentBlockSeeder"
php artisan optimize:clear
```

Or in admin: **Trust Verification → Content → Seed missing defaults**

## Admin URLs

| URL | Purpose |
|-----|---------|
| `/trust-verification/content` | Content hub (tabs) |
| `/trust-verification/content?tab=faq` | FAQ list |
| `/trust-verification/content/blocks/{id}/edit` | Edit block |

**Permission:** `trust_verification_settings` (Settings role)

## Public API

`GET /api/trust-verification/content/public`

Example (truncated):

```json
{
  "error": false,
  "message": "Content fetched successfully",
  "data": {
    "hub": {
      "hero_title": "Background verification you can trust",
      "trust_badges": [{ "icon": "🔒", "label": "Secure verification" }]
    },
    "wizard": {
      "consent_checkbox_text": "I confirm that I have permission..."
    },
    "faq": [{ "question": "...", "answer": "...", "sort_order": 1 }],
    "legal": {
      "terms": { "title": "...", "sections": [] },
      "privacy": {},
      "refund": {}
    },
    "testimonials": [],
    "report": { "disclaimer": "..." }
  }
}
```

Cached 5 minutes server-side.

## Changed files (plugin)

- `database/migrations/2026_05_24_000009_create_tv_content_blocks_table.php`
- `Models/TvContentBlock.php`
- `Services/TrustVerificationContentDefaults.php`
- `Services/TrustVerificationContentSanitizer.php`
- `Services/TrustVerificationContentService.php`
- `Services/TrustVerificationConsentService.php` (CMS consent + `legal_version` = `cms-v{n}`)
- `Services/TrustVerificationAuditLogService.php` (content audit actions)
- `Http/Controllers/Admin/TrustVerificationContentAdminController.php`
- `Http/Controllers/Api/TrustVerificationApiController.php` (`publicContent`)
- `routes/web.php`, `routes/api.php`
- `views/admin/content/*`, `views/admin/partials/nav.blade.php`
- `Mail/TvOrderSubmittedMail.php`, `Mail/TvReportReadyMail.php`
- `views/emails/*.blade.php`
- `database/seeders/TvContentBlockSeeder.php`

## Changed files (web frontend — deploy to homes)

- `web-fix/plugins/trust-verification/trustVerificationContentFallback.js`
- `web-fix/plugins/trust-verification/trustVerificationContentMerge.js`
- `web-fix/plugins/trust-verification/useTrustVerificationContent.js`
- `web-fix/plugins/trust-verification/trustVerificationApi.js`
- `web-fix/plugins/trust-verification/VerificationLegalPageFromCms.jsx`
- `web-fix/plugins/trust-verification/ui/VerificationHubPremium.jsx`
- `web-fix/plugins/trust-verification/ui/VerificationFaq.jsx` (via `items` prop)
- `web-fix/plugins/trust-verification/ui/VerificationConsentCheckbox.jsx`
- `web-fix/plugins/trust-verification/ui/VerificationDataUsageNotice.jsx`
- `web-fix/plugins/trust-verification/ui/VerificationReportDisclaimer.jsx`
- `web-fix/plugins/trust-verification/TrustVerificationPage.jsx`
- `web-fix/pages-verification-terms.jsx`, `privacy`, `refund-policy`

## Frontend fallback

1. On load, fetch `GET /content/public` once (module cache).
2. `mergeTrustVerificationContent()` overlays API values on `TRUST_VERIFICATION_CONTENT_FALLBACK`.
3. If API fails → full fallback object (same as current hardcoded copy).
4. If one block missing → only that field keeps fallback.
5. UI layout/CSS unchanged.

## Consent on orders

New orders store:

- `consent_text` from active `wizard.consent_checkbox_text` block
- `legal_version` = `cms-v{version}` (e.g. `cms-v3`)

Existing orders are never updated when CMS changes.

## Rollback

```bash
php artisan migrate:rollback --step=1
# Remove plugin CMS files if needed; frontend still works via fallback JS
```

## Test checklist

1. Edit FAQ in admin → hub FAQ updates after cache TTL or `optimize:clear`
2. Unpublish FAQ → hidden on frontend
3. Edit Privacy → `/verification-privacy` reflects CMS
4. Edit consent → new order has new `consent_text` / `legal_version`
5. Block API → site still renders (fallback)
6. Save `<script>` in HTML email → stripped
7. User without settings permission → cannot open Content
8. Edit/publish/reset → `tv_audit_logs` entries
9. `npm run build` on homes
10. `php artisan optimize:clear` on admin

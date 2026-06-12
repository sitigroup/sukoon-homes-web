# web-fix — Sukoon patch staging

**Do not treat this folder as wrteam core.** These are copies and patch scripts to deploy on top of eBroker/wrteam. Custom product logic lives in **Laravel plugins** (`app/Plugins/`) and **Next plugins** (`src/plugins/`).

## Deploy targets

| Local file | Server path |
|------------|-------------|
| `listing-location-fields.blade.php` | `app/Plugins/AreaListing/views/admin/partials/` |
| `maps-helper.js` | `public/assets/js/` |
| `sidebar-responsive.js` | `public/assets/js/` (use 1200px breakpoint; do not re-open sidebar after close) |
| `CategoryApiController.php` | `app/Http/Controllers/Api/` |
| `CashfreePayment.php` | `app/Services/Payment/` (unique link_id + reuse active pending links) |
| `paymentReturn.js` | `src/utils/` (sessionStorage return URL after package payment) |
| `SubscriptionSwiper.jsx` | `src/components/subscription-plan/` (success → return URL or dashboard) |
| `PaymentHandlers.jsx` | `src/components/subscription-plan/` |
| `PaymentSelectionModal.jsx` | `src/components/subscription-plan/` |
| `PaymentCheck.jsx` | `src/components/payment-status/` |
| `AddProperty.jsx` | `src/components/agent/property/` (stay on add flow after pay) |
| `AddProject.jsx` | `src/components/agent/project/` |
| `AppointmentApiController.php` | `app/Http/Controllers/Api/` |
| `ProfileApiController.php` | `app/Http/Controllers/Api/` |
| `create.blade.php`, `edit.blade.php`, `property-index.blade.php` | `resources/views/property/` |
| `property-table-scroll.css` | `public/css/` |
| `Header.jsx`, `Home.jsx`, `Layout.jsx`, `_app.js`, etc. | `homes.sukoon.group/src/...` (same relative path) |
| `AdvancedMapMarker.jsx` | `src/components/google-maps/` |
| `ViewAllSchedules.jsx`, `AddExtraTimeSlotModal.jsx` | `src/components/agent/appointment/modals/` |
| `areaListingApi.js`, `AreaSubAreaSelector.jsx` | Prefer `src/plugins/area-listing/` on server |

## One-time patch scripts (`patch-*.php`, `patch-*.py`)

Run on server against admin-homes when re-applying after a wrteam update. Do not re-run blindly if already applied.

## After deploy

```bash
cd /www/wwwroot/admin-homes && php artisan optimize:clear
cd /www/wwwroot/homes.sukoon.group && npm run build && pm2 restart homes-sukoon
```

## Final backup

```bash
bash scripts/final-sukoon-complete-backup.sh
```

See `RESTORE.md` at repo root.

# Area Wise Plugin — Changelog

**Product:** Sukoon Homes — Area Listing (Area Wise)  
**Version:** 2.0.0  
**Release date:** 21 May 2026  
**Environments:** admin-homes.sukoon.group · homes.sukoon.group  

---

## Summary

Version 2.0.0 completes the Area Wise location system for property and project listings: admin map/GPS workflows, agent permissions, customer admin controls, and frontend area filters. This release fixes map pin behaviour, prevents random area assignment on GPS, and adds server-side name matching when the map pin moves.

---

## 1. Admin property & project — Area Wise location

### 1.1 Location fields (listing-location-fields.blade.php)

- **Area Wise Location** block on property create/edit and project create/edit.
- Fields: State, City (synced from main form), Area, Sub Area, manual customer address.
- Hidden internal `state_id` / `city_id` selects (no Select2 on hidden fields — avoids stray dropdowns).
- Detected area/sub-area hidden fields for save when no DB match.
- `area_listing_admin_save=1` on admin forms for auto-create rules.
- **Get Current Location** uses browser GPS + Google reverse geocode (not raw coordinates for city/address).

### 1.2 Map integration (maps-helper.js)

- Single backend map instance (`initBackendPlacesMap`).
- Draggable marker; drag end calls server geocode API with client fallback.
- City search uses backend places list (`#places-suggestions`), not legacy Google Autocomplete on admin property pages.
- **Pin move:** updates address, city, state, country, lat/lng; dispatches `area-listing-location-filled` with geocode `result` in event detail.
- **Pin move:** does not open city suggestion dropdown (`__areaListingMapFillSilent`).
- Supports Advanced Marker `dragend` and `gmp-dragend`.

### 1.3 Legacy map conflict fix (custom.js)

- `initMap()` in `custom.js` defers to `initBackendPlacesMap` when `window.backendPlacesMapOptions` is set.
- Prevents duplicate map, stuck city Autocomplete dropdown, and drag handler that only updated latitude/longitude.

### 1.4 Script load order (property/project blades)

- `initMap` and `window.backendPlacesMapOptions` defined **before** Google Maps API script.
- Cache-bust maps-helper (e.g. `?v=20260521pinfix`).

---

## 2. GPS & area matching behaviour

| Behaviour | Admin GPS / Get Current Location | Map pin drag / place search |
|-----------|----------------------------------|-----------------------------|
| City / address source | Google reverse geocode | Google reverse geocode |
| Area auto-select | Exact name match in dropdown | Server `resolve-coordinates` then dropdown match |
| Random nearest area by coordinates | **Removed** | **Removed** |
| Select2 opens during apply | Blocked (GPS lock) | Blocked (map fill silent) |
| Old area when location changes | Cleared then re-matched | Cleared then re-matched |

### 2.1 extractPlaceComponents

- Reads `sublocality`, `neighborhood`, and formatted address (segment before city name, e.g. “Central Area” from full address string).

### 2.2 resolve-coordinates (admin POST)

- Route: `POST /area-listing/resolve-coordinates`
- Uses `AreaListingService::resolveNearestFromCoordinates` → **name-only** match via `resolveFromAddressNames` (no distance-based nearest area).
- Input: latitude, longitude, city_id, city, state, country, `location_components` JSON from Google.
- Returns: `area_id`, `sub_area_id`, names, match metadata.

### 2.3 Client apply flow

- `applyAreaSubAreaFromPlaceResult` → AJAX resolve-coordinates → `applyResolvedLocationFromServer` or client name match fallback.
- Select2 cleared with `val(null)` + `change.select2` so old area label does not stick.

---

## 3. Area & sub-area creation rules

### 3.1 Who can auto-create on save

| User type | Auto-create area/sub-area on save |
|-----------|-----------------------------------|
| Admin (`User` model or `area_listing_admin_save=1`) | Yes |
| Customer with `can_manage_area_listing = 1` | Yes |
| Other agents | No — suggestion queue for admin approval |

### 3.2 API (agent app)

- `GET /api/area-listing/permissions` — returns `can_manage_area_listing`, `can_auto_create_areas`, etc.
- `POST /api/area-listing/areas` and `sub-areas` — 403 if not allowed; use `suggest-area` / `suggest-sub-area` instead.
- Admin panel AJAX: `POST /area-listing/areas`, `POST /area-listing/sub-areas` with similar-exists prompts.

---

## 4. Per-agent “Area Wise” permission

### 4.1 Database

- Column: `customers.can_manage_area_listing` (boolean, default 0).

### 4.2 Admin customer module

- **List** (`/customer`): column **Area Wise** — Allowed / Not Allowed (agents only; “—” for non-agents).
- **Edit** → Agent Profile card: toggle **Area Wise** with dedicated **Save Area Wise Setting** button.
- Route: `PUT customer/{id}/area-listing-permission` → `updateAreaListingPermission()`.
- Fix: email uniqueness not checked when only saving Area Wise permission (removed “User already exists” false error).
- Fix: **Edit** action shown for all customers with update permission (not only `is_admin_added`).

### 4.3 Profile API

- Agent profile includes `can_manage_area_listing` for mobile/web app UI.

### 4.4 Frontend (homes.sukoon.group)

- `AreaSubAreaSelector.jsx`: **Add** vs **Suggest** based on permission.
- `areaListingApi.js`: permissions + suggest endpoints.

---

## 5. Area Listing admin plugin (core)

### 5.1 Admin UI

- States, cities, areas, sub-areas CRUD.
- Merge areas/sub-areas with audit log.
- Pending suggestions approval.
- CSV import with dry-run.
- Archived areas view.
- Property/project usage counts per area.

### 5.2 Services & controllers (fixes in 2.0.0)

- **AreaListingService:** save/filters/cache/`created_at`; auto-create guards; name resolve helpers.
- **AreaListingAdminController:** CRUD, merge, approve, normalize, `resolveCoordinates`.
- **AreaListingApiController:** permissions, store/suggest, project/property integration.
- **NearbyPlacesService:** alignment with area listing data.

---

## 6. Personalized feeds / filters (frontend)

- **Area Wise** filter on home/search via `AreaListingFilter.jsx`.
- Public display: Sub-area, Area, City, State (full address and map private to listing owner).

---

## 7. Files changed (deploy reference)

### Admin (admin-homes)

| File | Purpose |
|------|---------|
| `app/Plugins/AreaListing/views/admin/partials/listing-location-fields.blade.php` | Map, GPS, area sync, resolve-coordinates |
| `public/assets/js/maps-helper.js` | Backend map, geocode, pin drag |
| `public/assets/js/custom/custom.js` | initMap guard |
| `resources/views/property/create.blade.php`, `edit.blade.php` | Map script order, options |
| `resources/views/project/create.blade.php`, `edit.blade.php` | Same |
| `app/Plugins/AreaListing/Services/AreaListingService.php` | Business rules, resolve |
| `app/Plugins/AreaListing/Http/Controllers/Admin/AreaListingAdminController.php` | Admin + resolveCoordinates |
| `app/Plugins/AreaListing/Http/Controllers/Api/AreaListingApiController.php` | API permissions, CRUD |
| `app/Plugins/AreaListing/routes/web.php`, `api.php` | Routes |
| `app/Http/Controllers/CustomersController.php` | Customer list, permission update |
| `app/Models/Customer.php` | `can_manage_area_listing` |
| `resources/views/customer/index.blade.php`, `edit.blade.php` | UI |
| `public/assets/js/custom/formatter.js` | Area Wise column formatter |

### Frontend (homes.sukoon.group)

| File | Purpose |
|------|---------|
| `src/plugins/area-listing/AreaSubAreaSelector.jsx` | Agent area UI |
| `src/plugins/area-listing/areaListingApi.js` | API client |
| `src/components/.../AreaListingFilter.jsx` | Search filters |
| `src/components/google-maps/AdvancedMapMarker.jsx` | Draggable marker |

### Migrations / patches

- `customers.can_manage_area_listing` migration script.
- `web-fix/patch-*.php` — one-time server patches (see repo `web-fix/`).

---

## 8. Deployment checklist

```bash
cd /www/wwwroot/admin-homes
php artisan migrate   # if can_manage_area_listing not applied
php artisan optimize:clear
php artisan view:clear

cd /www/wwwroot/homes.sukoon.group
npm run build
pm2 restart homes-sukoon
```

**After deploy:** hard refresh admin property edit (Ctrl+F5) so `maps-helper.js` and compiled views reload.

---

## 9. Known limitations

- Area auto-select requires the Google-derived name to exist in the DB for that city (or a close normalized name match). Otherwise the dropdown clears and staff must pick or add the area once.
- **Client Address** field (if separate from main address) is not updated by map pin — only standard address/city/state/area fields in the Area Wise block.
- GPS accuracy warning shown when accuracy &gt; 3 km; user should drag pin if city is wrong.

---

## 10. Version history

| Version | Date | Notes |
|---------|------|-------|
| 1.0.0 | Earlier | Initial Area Listing plugin, CRUD, location tables |
| 1.5.0 | May 2026 | Personalized feeds, basic map fields |
| **2.0.0** | **21 May 2026** | GPS/map fixes, agent permission, customer admin, resolve-coordinates on pin move, legacy map guard |

---

**Document generated for Sukoon Group — Area Wise Plugin v2.0.0**

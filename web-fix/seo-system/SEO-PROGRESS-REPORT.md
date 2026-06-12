# SEO System — Progress Report

**Branch:** `seo-system` → `origin` (`https://github.com/sitigroup/sukoon-homes-web.git`)  
**Started:** 2026-06-03  
**Spec:** `SUKOON_SEO_EXECUTE.md`

---

## TASK A1 — robots.txt + noindex hygiene

**Status:** ✅ COMPLETE (2026-06-12)

### What changed

1. **`public/robots.txt`** — Sitemap → `https://homes.sukoon.group/sitemap.xml`; Disallow rules per spec + `/owner-dashboard`, `/tenant-dashboard`. (Cloudflare also prepends managed AI-bot rules; Sukoon rules append after.)
2. **`src/utils/seoNoIndexPaths.js`** — shared path matcher for private routes.
3. **`src/components/meta/MetaData.jsx`** — auto `noindex, nofollow` via `shouldNoIndexPath()` on client render.
4. **`pages/_document.js`** — SSR `<meta name="robots" content="noindex, nofollow">` for private paths (fixes view-source on static/client pages like `/login`).
5. **`pages/login/index.jsx`** — explicit `robots="noindex, nofollow"`.
6. **`pages/404.jsx`** — MetaData + `_document` SSR title for 404.
7. **`.env.example`** — documents `NEXT_PUBLIC_SEO=true` requirement.

### Files touched

| Staging (`web-fix/seo-system/`) | Production path |
|--------------------------------|-----------------|
| `robots.txt` | `public/robots.txt` |
| `seoNoIndexPaths.js` | `src/utils/seoNoIndexPaths.js` |
| `MetaData.jsx` | `src/components/meta/MetaData.jsx` |
| `_document.js` | `pages/_document.js` |
| `404.jsx` | `pages/404.jsx` |
| `login-index.jsx` | `pages/login/index.jsx` |
| `.env.example` | `.env.example` |

### Backups on server

- `public/robots.txt.backup-a1`
- `src/components/meta/MetaData.jsx.backup-a1`
- `pages/404.jsx.backup-a1`
- `pages/login/index.jsx.backup-a1`
- `pages/_document.js.backup-a1`

### VERIFY checklist

- [x] `curl https://homes.sukoon.group/robots.txt` — `Sitemap: https://homes.sukoon.group/sitemap.xml` + Disallow rules present
- [x] View-source `/login?lang=en` — `<meta name="robots" content="noindex, nofollow">` in initial HTML
- [x] View-source `/owner-dashboard?lang=en` — noindex meta in initial HTML
- [x] 404 (`/this-page-does-not-exist-seo-test`) — `<title>Page Not Found</title>` + noindex
- [x] `npm run build` passes on server (2026-06-12)
- [x] `NEXT_PUBLIC_SEO="true"` confirmed in production `.env`

### Deviations

- **Git:** No `.git` in workspace — branch/commits skipped; human should init git or commit from server mirror.
- **SSH:** Deploy uses `srv1534644-ipv4` (IPv6 alias `srv1534644` can hang).
- **SSR gap:** Entire `_app.js` is `"use client"`; added `_document.js` SSR injection so crawlers see noindex without waiting for hydration.
- **robots.txt:** Added `/owner-dashboard` and `/tenant-dashboard` disallows (live routes don't match `/dashboard` prefix alone).
- **Path matcher:** Extended beyond spec to `/my-*`, `/agent/*`, `/all-personalized-feeds`, `/404`.

---

## TASK A2 — Sitemap expansion + slug unification

**Status:** ✅ COMPLETE (2026-06-12)

### What changed

1. **`scripts/sitemap-generator.js`** — rewritten for sitemap **index** + child sitemaps; paginated API fetches; real `lastmod` from `updated_at`; Area Wise `/search/{city}/{area}/{subarea}/` URLs using **stored slugs** from AreaListing API.
2. **`pages/sitemap.xml.js`** — serves `<sitemapindex>` pointing to child sitemaps.
3. **`pages/sitemaps/[name].js`** — serves `static.xml`, `locations.xml`, `properties-{n}.xml`, `projects.xml`, `agents.xml` (articles omitted when empty).
4. **`src/utils/locationSearchUrl.js`** — prefers `city_slug`, `area_slug`, `sub_area_slug` from API; resolves cities via `getAreaListingCities`.
5. **`scripts/check-slug-consistency.js`** — one-time audit script for stored vs name-derived slugs.

### VERIFY checklist

- [x] `/sitemap.xml` is a sitemap index with child `<sitemap>` entries
- [x] Child sitemaps load (`static.xml`, `locations.xml`, `properties-1.xml`, `projects.xml`, `agents.xml`)
- [x] **Total URLs: 34** (static 15 + locations 11 + properties 5 + projects 1 + agents 2) vs inventory (5 properties, 1 project, 2 agents, 2 cities, 4 areas + sub-areas)
- [x] Location URLs present — e.g. `/search/barmer/baldev-nagar/` → HTTP 200
- [x] Slug consistency report: **0 mismatches** (4 areas, 2 cities checked)
- [x] Sample URLs return 200 (`/`, `/search/barmer/`, `/search/barmer/baldev-nagar/`, property + project detail)
- [x] `npm run build` passes on server

### Slug consistency output (2026-06-12)

```
Areas checked: 4 | Cities checked: 2 | Mismatches: 0
OK — all stored slugs match name-derived slugs.
```

### Deviations

- **`agents.xml`** added to index (agents were in old monolithic sitemap; not named in A2 spec child list but matches inventory).
- **`articles.xml`** omitted from index when API returns 0 articles.
- Extra city URL `baldev-nagar-barmer` appears because DB has a duplicate city row (id 28) — data cleanup is separate from SEO code.

### Backups on server

- `scripts/sitemap-generator.js.backup-a2`
- `pages/sitemap.xml.js.backup-a2`
- `src/utils/locationSearchUrl.js.backup-a2`

---

## TASK A3 — Wire Area Wise SEO + Organization entity

**Status:** ✅ COMPLETE (2026-06-12)

### What changed

1. **Area Manager admin** — SEO Title + SEO Description on area and sub-area edit modals; saved via `AreaListingAdminController` validators/payloads.
2. **AreaListing API** — `seo_title` / `seo_description` on areas and sub-areas in `areas` and `sub-areas` endpoints.
3. **Search SSR** — `src/utils/locationSeoMeta.js` + unified search page loaders use Area Wise meta when present (fallback: existing `seo_settings` + location suffix).
4. **`getDefaultSchemaMarkup()`** — Organization + RealEstateAgent `@graph`, `areaServed` (Barmer, Jodhpur, Rajasthan, India), `sameAs` / `knowsAbout` from env (`NEXT_PUBLIC_SCHEMA_*`, social URLs).

### VERIFY checklist

- [x] API returns `seo_title` / `seo_description` on areas (test: Baldev Nagar)
- [x] `/search/barmer/baldev-nagar/` `<head>` shows custom title + description after DB update
- [x] `/search/barmer/` still uses fallback (no area slug → no Area Wise override)
- [x] Homepage view-source includes `RealEstateAgent` + `Barmer` in JSON-LD (no `"Global"`)
- [x] `npm run build` + `php artisan optimize:clear` on server

### Test data (production)

Area `baldev-nagar` (id 67): `seo_title` = "Rent in Baldev Nagar Barmer | Sukoon Homes" (set via one-off script for verify; editable in admin UI).

### Backups on server

- `index.blade.php.backup-a3`, `area-actions.blade.php.backup-a3`, `sub-area-actions.blade.php.backup-a3`
- `AreaListingAdminController.php.backup-a3`, `AreaListingApiController.php.backup-a3`
- `helperFunction.js.backup-a3`

---

## TASK A4 — Property JSON-LD + SSR content

**Status:** ✅ COMPLETE — **⏸ PAUSE** (human review required before Phase B)

### What changed

1. **`src/utils/jsonld.js`** — `realEstateListing()`, `propertyBreadcrumbList()`, `searchBreadcrumbList()`, `mergeStructuredData()`.
2. **`pages/property-details/[slug]/index.jsx`** — removed `dynamic(..., { ssr: false })`; static import; GSSP fetches full property + builds JSON-LD; passes `initialPropertyLoad` for SSR body.
3. **`PropertyDetailPage.jsx` / `PropertyDetailsSwitcher.jsx`** — removed inner `ssr: false`; thread `initialPropertyLoad`.
4. **`CustomPropertyDetailsPage.jsx` / `PropertyDetails.jsx`** — SSR initial state + skip duplicate client fetch; panorama `useEffect` guarded for `window`/`document`.
5. **Search pages** — `BreadcrumbList` JSON-LD via updated `search-location-page.jsx`.

### Pre-deploy build

`npm run build` on server **passed** before `pm2 restart homes-sukoon` (2026-06-12). No hydration errors observed in browser on `/property-details/home-for-rent/` (`__NEXT_DATA__.err` null; body text ~2800 chars).

### TTFB (curl `time_starttransfer`, 3 property URLs)

| Slug | Before | After | Δ |
|------|--------|-------|---|
| `home-for-rent` | 3.12s | 3.55s | +433ms |
| `flat-for-rent` | 3.49s | 3.39s | −100ms |
| `2-bhk-flat-for-rent` | 2.42s | 3.46s* | +1.04s |

\*`2-bhk-flat-for-rent` returned HTTP 500 on one probe (transient); retry was 3.46s. Median delta on healthy pages ≈ **+170ms**; `home-for-rent` spike +433ms (within noisy SSR range but above 300ms target on that slug).

### Privacy JSON-LD (`home-for-rent`, `can_view_exact_location: false`)

- `structuredData`: **no** `streetAddress`, **no** `latitude`, **no** `geo`
- `addressLocality`: `Gali Wala` (area-level only)

### VERIFY checklist

- [x] View-source / `__NEXT_DATA__` includes property title + `RealEstateListing` + `BreadcrumbList`
- [x] Privacy-protected listing JSON-LD has no exact address/geo coordinates
- [x] Build passes before deploy
- [~] TTFB: one slug +433ms; others flat or improved (see table)
- [x] No hydration errors in manual browser check
- [x] **PAUSE** for human review

### Rollback (exact commands)

```bash
ssh srv1534644-ipv4
W=/www/wwwroot/homes.sukoon.group
P=$W/src/plugins/property-detail-switcher
cp -a $W/pages/property-details/[slug]/index.jsx.backup-a4 $W/pages/property-details/[slug]/index.jsx
cp -a $W/src/components/pagescomponents/PropertyDetailPage.jsx.backup-a4 $W/src/components/pagescomponents/PropertyDetailPage.jsx
cp -a $P/PropertyDetailsSwitcher.jsx.backup-a4 $P/PropertyDetailsSwitcher.jsx
cp -a $P/CustomPropertyDetailsPage.jsx.backup-a4 $P/CustomPropertyDetailsPage.jsx
cp -a $W/src/components/property-detail/PropertyDetails.jsx.backup-a4 $W/src/components/property-detail/PropertyDetails.jsx
rm -f $W/src/utils/jsonld.js
# Optional: restore search pages from git checkout or a3 copies
cd $W && npm run build && pm2 restart homes-sukoon
```

Backups kept at `*.backup-a4` beside each file on server.

### Deviations

- JSON-LD is in GSSP `structuredData` → `MetaData`; with `"use client"` `_app.js`, `<script type="application/ld+json">` may hydrate client-side — data is present in `__NEXT_DATA__.pageProps.structuredData` for crawlers.
- `addressLocality` uses finest public area name (sub-area when present), not street-level text.

---

**⏸ PAUSE — Please review this report before Phase B (TASK B1).**

---

## HTTP 500 investigation — `/property-details/2-bhk-flat-for-rent/` (pre-B1)

| Source | Finding |
|--------|---------|
| Laravel API | `GET get_property?slug_id=2-bhk-flat-for-rent` → HTTP 200, `"No Data Found"`, `data: []` |
| PM2 logs | `TypeError: Cannot read properties of null (reading 'area_listing')` in GSSP (`property-details/[slug].js`) |
| Root cause | A4 `propertyBreadcrumbList(null, lang)` — explicit `null` bypasses default param `property = {}` |
| Secondary | Occasional `ECONNRESET` on API proxy (transient, separate) |

**Verdict:** Not a network blip — reproducible code bug when property slug missing from API.

**Hotfix (deployed, staged in `web-fix/seo-system/a4/`):**
- `jsonld.js` — `propertyBreadcrumbList` returns `null` when no `property.slug_id`
- `property-details-index.jsx` — `return { notFound: true }` when property missing

**Post-fix:** slug returns **HTTP 404** (verified 2026-06-12).

---

## TASK B1 — SeoEngine plugin scaffold + admin control plane

**Status:** ✅ Deployed (2026-06-12)

**Staged:** `web-fix/plugins/SeoEngine/src/` + `web-fix/patch-seo-engine-*.php` + `web-fix/seo-system/b1/deploy-b1-remote.sh`

### What changed

- New plugin `app/Plugins/SeoEngine/` — ServiceProvider, 7 migrations (`seo_engine_*` tables), settings service (10-min cache), admin Dashboard + Global Settings, public API `GET /api/seo-engine/settings`.
- Registered in `config/app.php` via `patch-seo-engine-register.php` (backup: `config/app.php.bak-seo-engine-20260612-201156`).
- Sidebar link before stock SEO Settings (backup: `sidebar.blade.php.bak-seo-engine-20260612-201156`).

### Deploy note

Unzip/SCP as **root** caused PHP-FPM `Permission denied` on plugin files until `chown -R www:www app/Plugins/SeoEngine`. Deploy script updated to fix ownership automatically.

### DB backup

Pre-migration backup attempted; long `mysqldump` was interrupted. Migrations are additive only (new `seo_engine_*` tables). Run `web-fix/seo-system/b1/backup-db.sh` on server before B2 if no fresh backup exists.

### VERIFY checklist

- [x] Migrations run clean — 7 tables: `seo_engine_settings`, `pages`, `slug_history`, `redirects`, `locality_stats`, `qa_pages`, `404_log`
- [x] Plugin registered; admin menu item **SEO Engine** (requires `dashboard` / `settings` permissions on module `seo_engine` — assign in Roles if not visible)
- [x] Settings seeded with defaults (identity, sameAs, knowsAbout, index threshold 3, budget bands, schema toggles, robots/llms text, AI-bot policy)
- [x] `/api/seo-engine/settings` returns safe subset only — no `robots_txt`, `llms_txt`, API keys, or cron internals
- [x] Cache clear busts both `seo_engine:settings:all` and `seo_engine:api:public_settings`

### API sample (public fields)

`site_name`, `site_url`, `logo_url`, `site_description`, `same_as`, `knows_about`, `index_threshold`, `budget_bands`, `schema_toggles`, `ai_bot_policy`

---

## TASK B2 — Redirects + slug history

**Status:** ✅ Deployed (2026-06-12)

**Commits:** `53f0c19` (B2 code), A4 hotfix `cfdd666`

### VERIFY checklist (2026-06-12)

| Check | Result |
|-------|--------|
| Slug change on **inactive** property id 27 (`3-bhk-flat-for-rent`, status=0) → auto redirect row | ✅ PASS |
| Old URL `301` via middleware | ✅ `location: /property-details/3-bhk-flat-for-rent-b2verify-…/` |
| Chain A→B→C flattens to A→C | ✅ PASS (`test-redirect-service.php`) |
| Hit counter increments on API resolve | ✅ PASS (+1 per call) |
| Loop prevention | ✅ PASS (Y→X blocked when X→Y exists) |
| Middleware scope | ✅ `matcher` only `property-details`, `project-details`, `article-details`, `rent`; `/` and `/search/` return **200** with no `Location` |

### Slug investigation (recap)

`2-bhk-flat-for-rent` — **no DB row**; closest inactive id 27 is `3-bhk-flat-for-rent`. Not a slug mismatch.

---

## TASK B3 — Generator + locality stats + templates

**Status:** ✅ Deployed (2026-06-12) — **⏸ PAUSE** (human review required before B4)

**Commits:** `b3648c1` (B3 code), `f685198` (verify script only)

### Deploy (2026-06-12)

1. Uploaded `web-fix/plugins/SeoEngine/src/` → `/www/wwwroot/admin-homes/app/Plugins/SeoEngine/` (zip + unzip)
2. `chown -R www:www app/Plugins/SeoEngine`
3. Migration `2026_06_12_000002_create_seo_engine_templates_table` — DONE
4. `php artisan optimize:clear`
5. `scripts/sitemap-generator.js` copied to homes (backup: `sitemap-generator.js.backup-b3`)

### Hotfix during deploy

`SeoEngineTemplateService::save()` used `skip(5)` without `limit` — MariaDB error 1064 on first `seo-engine:generate-pages`. Fixed on server (and staged locally): keep newest 5 versions via `limit(5)` + `whereNotIn` delete.

### Scheduler cron

| Check | Result |
|-------|--------|
| aaPanel cron runs `schedule:run` every minute | ✅ `*/1 * * * * /www/server/cron/d5fb6e7700cf2b0ac31d51fc3a28edd9` |
| Command | `cd /www/wwwroot/admin-homes && /usr/bin/php artisan schedule:run --quiet` |
| Nightly generator | Registered in `SeoEngineServiceProvider` at 02:00 (`seo-engine:generate-pages`) |

### First generation run

```
php artisan seo-engine:generate-pages
php artisan seo-engine:build-sitemaps
```

| page_type | count |
|-----------|------:|
| rent_city | 2 |
| rent_area | 4 |
| rent_subarea | 5 |
| rent_combo_bhk | 16 |
| rent_combo_type | 12 |
| rent_combo_budget | 16 |
| **Total** | **55** |

| Rollup | count |
|--------|------:|
| location (city+area+subarea) | 11 |
| combo (bhk+type) | 28 |
| budget | 16 |

| Indexable | count |
|-----------|------:|
| `is_indexable=true` | 1 |
| `is_indexable=false` | 54 |

**Why only 1 indexable:** index threshold = 3; only `/rent/baldev-nagar-barmer/` has 4 active rent listings (duplicate city row id 28). ~5 active rent listings site-wide.

### Sample generated titles (top by listing_count)

1. `/rent/baldev-nagar-barmer/` — *Flats & Houses for Rent in Baldev Nagar Barmer | Sukoon Homes* (4 listings, indexable)
2. `/rent/barmer/` — *Flats & Houses for Rent in Barmer | Sukoon Homes* (1 listing)
3. `/rent/barmer/baldev-nagar/` — *Rent in Baldev Nagar, Barmer — 1 Listings | Sukoon Homes*
4. `/rent/barmer/baldev-nagar/under-rs10000/` — *Rent under ₹8000 in Baldev Nagar, Barmer | Sukoon Homes*
5. `/rent/barmer/baldev-nagar/gali-wala/` — *Gali Wala Rentals, Baldev Nagar Barmer | Sukoon Homes*

### Locality stats

- **9 rows** in `seo_engine_locality_stats`

### Rent sitemap

| Source | URL count |
|--------|----------:|
| `storage/app/seo-engine/rent-pages.json` | 1 |
| `GET /api/seo-engine/rent-sitemap` | 1 (`/rent/baldev-nagar-barmer/`) |
| Live `/sitemap.xml` index | **Not yet** — `rent-pages.xml` child missing until `npm run build && pm2 restart homes-sukoon` (Next.js bundles sitemap generator at build time) |

### IndexNow

- `indexnow_key` **not configured** in SEO Engine settings → ping skipped (expected until key added + `/{key}.txt` on web root)

### PG category / type facet

| Check | Result |
|-------|--------|
| `categories` table | **Yes** — id **8**, name `PG/Room` |
| Active listings with PG in title | 0 |
| Generator `TYPE_FACETS` in code | `flat`, `house`, `apartment` only — **PG not included** |
| `rent_combo_type` pages with `/pg/` path | 0 |

**Action for human:** Decide whether to add `pg` to `TYPE_FACETS` in `SeoEnginePageGeneratorService.php` (maps to category slug).

### VERIFY checklist

- [x] Generator run: 55 registry rows; counts align with ~5 active rent listings
- [x] Below threshold → `is_indexable=false` (54/55); `quality_score` populated
- [~] Template admin + version history — deployed; not manually UI-tested this session
- [~] rent sitemap in **live** sitemap index — pending frontend rebuild
- [~] IndexNow ping on property update — skipped (no key)
- [x] **PAUSE** for human review

### Commit f685198

`seo: add B3 deploy verification script` — adds only `web-fix/seo-system/b3/verify-b3-full.php` (server-side JSON report script). No plugin code changes in that commit.

---

**⏸ PAUSE — Please review this report before TASK B4.**

### B3 review fixes (2026-06-12) — commit `136843a`

| # | Fix | Result |
|---|-----|--------|
| 1 | MariaDB `OFFSET` hotfix in `SeoEngineTemplateService::save()` | ✅ Committed + deployed |
| 2 | Budget band path/title vs admin settings | ✅ Title uses `{band_label}`; path slug from label (`under-rs10000` ↔ "Under ₹10,000"); listing metrics no longer overwrite band values |
| 3 | Pluralization | ✅ `1 Listing` / `2 Listings` via `{listings_word}` in templates + render() |
| 4 | `type_facets` in Global Settings | ✅ Admin repeater; seeded `flat, house, apartment, pg`; generator reads settings; **4 pg facet pages** generated |
| 5 | IndexNow | ✅ Key `2d03c06a5f6a6bf476b65d275f5a1fcb` saved; `/{key}.txt` HTTP 200; test ping **ok** |
| 6 | Frontend sitemap | ✅ `npm run build` + PM2 restart; `/sitemap.xml` lists `rent-pages.xml` (1 URL) |

**Admin budget bands on server (from settings DB):** Under ₹10,000 (2999–9999), ₹10k–15k, ₹15k–25k, ₹25k+ (25000–49999).

**Sample after regenerate:** `/rent/barmer/baldev-nagar/under-rs10000/` → title *Rent Under ₹10,000 in Baldev Nagar, Barmer | Sukoon Homes*

**Pending (human):** Fix city id 28 duplicate in admin, then click Regenerate in dashboard.

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

**Admin cleanup (2026-06-13):** Junk city id 28 (`baldev-nagar-barmer`), typo area Raj Colneyer, sub-area Saadsd removed in Area Wise; all locations consolidated under Barmer (id 22). Regenerate + `regen-and-purge.php` purged 29 stale registry rows.

---

## TASK B5 — /rent/ frontend, dynamic robots/llms, bot-files API

**Status:** ✅ CLOSED (2026-06-13)

### B5 review fixes (frontend + generator root cause)

| # | Fix | Result |
|---|-----|--------|
| 1 | **`?lang=en?lang=en` double param** | ✅ Removed manual `?lang=` from all `CustomLink` hrefs in rent components — `CustomLink` already appends locale |
| 2 | **PopularSearches includes current page** | ✅ Filtered in GSSP + `PopularSearches` component (`currentPath` exclusion) |
| 3 | **Listing cards show stale "Baldev Nagar Barmer"** | ✅ `SeoEnginePageDataService` uses `COALESCE(area_listing_cities.name, propertys.city)` from `area_listing_property_locations` join |
| 4 | **Junk path recreation root cause** | ✅ See below — **not** property free-text; **active Area Wise city row id 28** |
| 5 | **Generator hardening** | ✅ Skip composite-slug orphan cities + require property-location assignments; prune stale `/rent/` registry rows each run |
| 6 | **Double-run verify (no purge)** | ✅ Two consecutive `seo-engine:generate-pages` → **71 pages**, **0 junk**, stable on run 2 |

### Junk recreation — root cause (precise)

| Source | Finding |
|--------|---------|
| **`area_listing_cities`** | City **id 28** `baldev-nagar-barmer` still **`status=1`** — generator iterates all active cities |
| **`area_listing_areas`** | Area **id 72** `raj-colony` still active under city 28 (0 property assignments) |
| **`area_listing_property_locations`** | All 4 active rent properties assigned to **city_id=22 (Barmer)** — **none** to city 28 |
| **`propertys.city` free-text** | Demo props 34–36 still say "Baldev Nagar Barmer" — **display only**; does **not** drive page generation |
| **Cached API / generator logic** | Generator reads **Area Wise hierarchy only** (`City` → `Area` → `SubArea`), not property strings |

**Admin data still to clean (recommended):**
1. **Deactivate or delete city id 28** (`Baldev Nagar Barmer`) in Area Wise — duplicate of Barmer + Baldev Nagar area under city 22
2. **Deactivate area id 72** (`Raj Colony`) under city 28 if city row remains
3. **Optional:** Update demo properties 34–36 free-text `city` field to `Barmer` for consistency in admin/property cards elsewhere

**Generator hardening (deployed):** `shouldGenerateCity()` skips cities whose slug ends with `-{other_city_slug}` (e.g. `baldev-nagar-barmer` when `barmer` exists) **and** cities with zero approved rent rows in `area_listing_property_locations`. `pruneStaleRentPages()` deletes unlocked `/rent/` registry rows not emitted in the current pass — **no nightly purge script required**.

**Deploy note:** Plugin must rsync to `app/Plugins/SeoEngine/` (flat autoload path), not only `app/Plugins/SeoEngine/src/` — fixed in `deploy-b5-remote.sh`.

### What changed

1. **Next.js `/rent/[[...segments]]` route** — GSSP fetches `GET /api/seo-engine/page`; `Cache-Control: public, s-maxage=21600, stale-while-revalidate=86400` on `/rent/` only (not `/property-details/`).
2. **`RentPageView` + components** — SSR H1, intro, listing cards, FAQ accordion, breadcrumb + internal link blocks, `PopularSearches`.
3. **JSON-LD** — `BreadcrumbList`, `ItemList`, `FAQPage` merged into page structured data.
4. **Robots for combo pages** — `is_indexable=false` → normal 200 render with `noindex, nofollow` in `pageProps.robots` **and** `X-Robots-Tag` response header (hotfix in GSSP).
5. **Redirect + 404 flow** — unknown path checks `GET /api/seo-engine/redirect`; miss logs via `POST /api/seo-engine/404-log` then Next 404.
6. **Dynamic `robots.txt` / `llms.txt`** — `pages/robots.txt.js` + `pages/llms.txt.js` fetch SeoEngine API; static `public/robots.txt` removed (backup `public/robots.txt.backup-b5`).
7. **Plugin API** — `SeoEngineBotFilesApiController` (`/api/seo-engine/robots-txt`, `/api/seo-engine/llms-txt`) merges A1 disallows + admin robots body + AI-bot policy + sitemap line; `SeoEngine404LogApiController` for rent 404 hits; settings cache bust for bot-file keys.

### Files (staging → production)

| Staging (`web-fix/seo-system/b5/`) | Production path |
|-----------------------------------|-----------------|
| `rent-[[...segments]].jsx` | `pages/rent/[[...segments]]/index.jsx` |
| `RentPageView.jsx`, `RentPageComponents.jsx`, `rentPageApi.js`, `jsonld-rent.js` | `src/plugins/seo-engine/` |
| `robots.txt.js`, `llms.txt.js` | `pages/robots.txt.js`, `pages/llms.txt.js` |
| `deploy-b5-remote.sh` | run from workstation |
| `regen-and-purge.php`, `audit-registry.php`, `verify-b5-rent.php` | server `/tmp/` helpers |

| Plugin (`web-fix/plugins/SeoEngine/src/`) | Production |
|---------------------------------------------|------------|
| `SeoEngineBotFilesApiController.php` | `app/Plugins/SeoEngine/...` |
| `SeoEngine404LogApiController.php`, `SeoEngine404Log.php` | same |
| `routes/api.php`, `SeoEngineSettingsService.php` (cache keys) | same |

### VERIFY checklist

- [x] `curl -sI /rent/barmer/?lang=en` → `X-Robots-Tag: index, follow` + `Cache-Control: public, s-maxage=21600...`
- [x] `curl -sI /rent/barmer/baldev-nagar/?lang=en` → `X-Robots-Tag: noindex, nofollow` (combo below threshold, 0 listings)
- [x] View-source indexable page — title *Flats & Houses for Rent in Barmer | Sukoon Homes*, 3 listings, structured data in `__NEXT_DATA__`
- [x] View-source noindex combo — `pageProps.robots` = `noindex, nofollow`, 0 listings, structured data present
- [x] `/robots.txt` — dynamic; includes `Sitemap:`, `Disallow: /login`, GPTBot / Cloudflare content signals
- [x] `npm run build` + PM2 `homes-sukoon` restart after removing static `public/robots.txt`
- [x] Registry after generator hardening — **71 pages**, **1 indexable** (`/rent/barmer/`), **0 junk paths** (double-run verified)
- [x] Live `/rent/barmer/` — no `?lang=` duplication in hrefs; all listing cards show **Barmer**
- [x] Rent sitemap — 1 URL (`/rent/barmer/`) in `rent-pages.json` and live sitemap index
- [~] PageSpeed mobile `/rent/barmer/` — **not scored this session** (PSI API daily quota 429; server has no Chrome for Lighthouse). Run manually: [PageSpeed Insights](https://pagespeed.web.dev/analysis?url=https%3A%2F%2Fhomes.sukoon.group%2Frent%2Fbarmer%2F%3Flang%3Den&form_factor=mobile)

### Registry snapshot (2026-06-13 post-hardening)

| page_type | count |
|-----------|------:|
| rent_city | 1 |
| rent_area | 5 |
| rent_subarea | 5 |
| rent_combo_bhk | 20 |
| rent_combo_type | 20 |
| rent_combo_budget | 20 |
| **Total** | **71** |

| Indexable paths | count |
|-----------------|------:|
| `/rent/barmer/` | 1 |

| Junk paths (`baldev-nagar-barmer`, `raj-colneyer`, `saadsd`) | **0** after 2 consecutive generates |

### Deviations

- **`meta name="robots"`** not in view-source for rent pages (same as A1 client `MetaData` pattern); crawlers receive **`X-Robots-Tag`** + `pageProps.robots` for SSR routes.
- **PopularSearches** empty when only 1 indexable path exists (expected).

### Commits

- `6118628` — `seo: task B5 — /rent/ frontend, dynamic robots/llms, bot-files API`
- *(pending)* — B5 review fixes: frontend bugs + generator hardening

---

## TASK B6 — AI content engine ✅ CLOSED

### Delivered

1. **Artisan** `seo-engine:generate-content` — Gemini (direct API, 2048 tokens) + Claude provider; rate limit, retry/backoff, failure log.
2. **Prompt contract** — injected facts only; verifiable locality language; no listing counts in prose; area-scoped rent on 0-listing combos; ₹ symbol (never INR); `-ise` Indian English; five rotating opening patterns; min-listings gate (default 1).
3. **Admin** — AI settings (provider, rate limit, prompt template, min listings); **Content review** queue (approve/regenerate/lock).
4. **Storage** — `intro_html`, `faq_json`, `content_generated_at`, `content_review_status`; auto-regenerate on >15% listing drift (skip locked).

### Bulk generation (2026-06-13)

| Metric | Value |
|--------|------:|
| Pages with `listing_count >= 1` | 10 |
| AI content generated (total) | 10 |
| Approved (sample review) | 4 |
| Pending review (bulk run) | 6 |
| Template fallback (0-listing combos) | 61 |
| Failures in log (earlier debug runs) | 6 |
| Bulk run failures (final) | 0 |

**API cost (estimated):** Gemini `usageMetadata` now captured. For ~10 pages at ~2,000–2,500 tokens/page (prompt + completion), total ≈ **20k–25k tokens** → **~$0.002–0.004 USD** at gemini-2.5-flash-lite list rates ($0.075/1M input, $0.30/1M output). Not billed per-page in admin UI; use `tokens_total` / `est_cost_usd` from artisan output on future runs.

### VERIFY checklist

- [x] 5 test pages reviewed and **approved** (4 AI + 1 template fallback on `/rent/barmer/kailash-puri/1bhk/`)
- [x] Locked page untouched by bulk; failures logged not crashed
- [x] Min-listings gate: bulk skips 0-listing pages

### Commits

- `seo: task B6 — AI content engine, prompt rules, min-listings gate`

---

## TASK B7 — Q&A Hub + monitoring ✅ CLOSED

### Admin publish redirect fix

**Root cause:** The edit form POSTs to `PUT /seo-engine/qa/{id}`. There was no `GET /seo-engine/qa/{id}` route — only `/qa/{id}/edit`. After save or failed validation, the browser landed on `/seo-engine/qa/8` → **404** (broken admin route, not a public guide URL).

**Fix:** `GET /seo-engine/qa/{qaPage}` → redirect to edit; successful publish/save → redirect to **edit** with success flash; validation errors → redirect to **edit** with errors (not the bare PUT URL).

**User guide id 8:** Row **not present** in DB (gap in ids 1–7, 9–25). **No published status was saved** — all guides remained draft until verify script published id 1. Re-publish from admin after deploy; redirect will stay on edit with “Q&A guide published.”

### Delivered

| Layer | Files |
|-------|--------|
| **Plugin** | `SeoEngineQaPage` model, admin CRUD (`/seo-engine/qa`), API (`/api/seo-engine/qa-page`, `/qa-sitemap`), `SeoEngineQaSitemapService`, `SeoEngineQaSeedService` + `seo-engine:seed-qa`, `seo-engine:digest-404` (weekly schedule) |
| **Dashboard** | Q&A draft count, 404 hits (7d), IndexNow log, 404 digest widget |
| **Web** | `pages/guides/[category]/[slug]/index.jsx`, `GuidePageView.jsx`, `guidePageApi.js`, `jsonld-guide.js` (FAQPage + Article) |
| **Sitemap** | `qa-guides.json` export; `sitemap-generator.js` child `qa-guides.xml` (published only) |

### VERIFY checklist (2026-06-13)

- [x] Published guide **HTTP 200** at `/guides/rent-agreements/what-is-stamp-duty-on-rent-agreements-in-rajasthan/?lang=en`
- [x] `__NEXT_DATA__` includes `direct_answer`, **FAQPage** + **Article** JSON-LD
- [x] `qa-guides.json` count **1**; `/sitemap.xml` lists `qa-guides.xml`; child contains guide URL
- [x] `npm run build` + PM2 `homes-sukoon` restart completed
- [x] 404 digest command + dashboard widgets deployed

### Commits

- `044eea8` — seo: task B7 — Q&A hub, monitoring widgets, 25 draft seeds
- *(this commit)* — B7 admin redirect fix, guide API base URL, GuidePageView import

---

## FINAL PROJECT SUMMARY — Sukoon SEO Execute (A1 → B7)

| Task | Title | Status |
|------|-------|--------|
| A1 | Robots / noindex hygiene | ✅ |
| A2 | Sitemap index expansion | ✅ |
| A3 | Area Wise SEO admin + Organization schema | ✅ |
| A4 | Property JSON-LD + SSR detail | ✅ |
| B1 | SeoEngine plugin scaffold | ✅ |
| B2 | Redirects + slug observers | ✅ |
| B3 | Page generator + locality stats + rent sitemap | ✅ |
| B4 | Pages admin + registry API | ✅ |
| B5 | `/rent/` frontend + dynamic robots/llms | ✅ |
| B6 | AI content engine + review queue | ✅ |
| B7 | Q&A hub + monitoring | ✅ |

### Git (`seo-system` branch)

**~18 SEO commits** from `7b29243` (A1+A2) through B7 fix (see `git log --oneline seo-system`).

### Live registry (2026-06-13)

| Metric | Count |
|--------|------:|
| **Rent pages** (`seo_engine_pages`) | **71** |
| **Indexable rent pages** | **1** (`/rent/barmer/`) |
| **AI intro + FAQ** (listing_count ≥ 1) | **10** |
| **Q&A guides** (total) | **24** (id 8 gap) |
| **Q&A published** | **1** (verify; rest draft) |
| **Junk rent paths** | **0** |

### Production URLs

- Indexable rent: `https://homes.sukoon.group/rent/barmer/?lang=en`
- Sample guide: `https://homes.sukoon.group/guides/rent-agreements/what-is-stamp-duty-on-rent-agreements-in-rajasthan/?lang=en`
- Sitemaps: `https://homes.sukoon.group/sitemap.xml` → `rent-pages.xml` (1) + `qa-guides.xml` (1)

---

## Phase C — Richer pages + leads + analytics

**Status:** ✅ LIVE (2026-06-13) — C1/C2 verified on production; C3/C4 await admin credentials

**Spec:** `SUKOON_SEO_PHASE_C.md` (if present in repo)

### C1 — Richer `/rent/` page design

| Staging | Deploy target |
|---------|---------------|
| `web-fix/seo-system/c1/RentPageView.jsx` | `src/plugins/seo-engine/RentPageView.jsx` |
| `web-fix/seo-system/c1/RentPageComponents.jsx` | `src/plugins/seo-engine/RentPageComponents.jsx` |
| `web-fix/seo-system/c1/rentLayout.js` | `src/plugins/seo-engine/rentLayout.js` |
| `web-fix/seo-system/c1/rent-[[...segments]].jsx` | `pages/rent/[[...segments]].jsx` |

Sections (top → bottom): shell header/footer, hero + **ShellSearchBar**, trust strip, rent insight + sparkline, listing grid (photos + verified badge), lazy OSM map, nearby landmarks, lead capture (×2: alert modal + bottom band), AI intro/FAQ, internal link chips. Zero listings → lead form primary. Content width uses `RentShellContainer` (`max-w-6xl`) aligned with shell header/footer at all breakpoints.

### C2 — Lead capture

- Migration: `seo_engine_leads`
- API: `POST /api/seo-engine/leads` (rate limit 5/hr/IP, honeypot `website`)
- Admin: **SEO Engine → Leads** (filter, status, CSV export)
- WhatsApp events: `seo_engine_lead` (team), `seo_engine_lead_auto_reply` (renter) — map templates in WhatsApp admin
- Settings: team notify phone, area hero JSON, default hero URL

### C3 — GSC performance dashboard

- **SEO Engine → Performance** — GSC OAuth connect, daily cache, top queries/pages, movers, low-CTR alerts
- Per-page GSC column on Pages admin when cache populated
- Settings: GSC property + OAuth client ID/secret

### C4 — GA4

- Settings: measurement ID + enable toggle
- `RentGa4Tracker` — `page_view`, `listing_click`, `lead_submit` events on `/rent/`

### Deploy

```bash
bash web-fix/seo-system/c1/deploy-c1-remote.sh
```

Backup first: `bash scripts/final-sukoon-complete-backup.sh`

### VERIFY checklist (post-deploy)

- [x] `/rent/barmer/` — all sections render (2026-06-13)
- [x] `__NEXT_DATA__` structured data: `BreadcrumbList`, `ItemList`, `FAQPage` (+ Q/A nodes)
- [x] Lead API `POST /api/seo-engine/leads` → HTTP 201, row id **1** in `seo_engine_leads` (`Phase C Verify`)
- [x] SeoEngine plugin + `WhatsappEventCatalog` (`seo_engine_lead`, `seo_engine_lead_auto_reply`) synced to server
- [x] **Lead notify phone** set to `+919990687827`
- [ ] **You:** WhatsApp admin → map templates to `seo_engine_lead` + `seo_engine_lead_auto_reply`
- [ ] **You:** SEO Engine → Leads — confirm UI + CSV export
- [ ] **You:** SEO Engine → Performance → GSC OAuth (property + client ID/secret in Settings)
- [ ] **You:** SEO Engine → Settings → GA4 measurement ID + enable → DebugView events
- [ ] Mobile PageSpeed ≥85 (Cloudflare) — optional spot-check

### Human config (admin-homes)

| Setting | Where | Status |
|---------|--------|--------|
| Lead notify phone | SEO Engine → Settings | **+919990687827** |
| GSC property + OAuth | SEO Engine → Settings → Performance | Not set |
| GA4 `G-…` + enable | SEO Engine → Settings | Disabled |
| WhatsApp templates | WhatsApp → Events | Catalog deployed; templates unmapped |

Verify scripts (server): `web-fix/seo-system/c1/verify-phase-c.php`, `verify-phase-c-admin.php`

---

**✅ SEO EXECUTE COMPLETE — Phase C live on `/rent/barmer/`. Finish C3/C4 + WhatsApp mapping in admin when credentials are ready.**

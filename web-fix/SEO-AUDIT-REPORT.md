# SEO Implementation Audit — Sukoon Homes

**Scope:** Production stack at `admin-homes.sukoon.group` (Laravel) + `homes.sukoon.group` (Next.js eBroker 1.4.0).  
**Method:** Read-only inspection of server paths and local `web-fix/` copies. No code was changed.  
**Audit date:** 12 Jun 2026

---

## 1. Existing SEO Features (Backend — Laravel)

### 1.1 Tables, columns, and settings

| Area | Location | Fields / notes |
|------|----------|----------------|
| **Global per-page SEO** | `database/migrations/2024_01_02_053848_create_seo_settings_table.php` → model `app/Models/SeoSettings.php` | `page`, `title`, `description`, `keywords`, `image`, `schema_markup` |
| **Properties** | `database/migrations/2024_01_02_054734_add_columns_to_propertys_table.php` | `meta_title`, `meta_description`, `meta_keywords`, `meta_image` (nullable). Backfill sets `meta_title` from `title`. |
| **Projects** | `database/migrations/2024_02_27_100246_create_projects_table.php` | Same meta_* pattern |
| **Categories** | `database/migrations/2024_01_02_055754_add_columns_to_categories_table.php` | `meta_title`, etc. |
| **Articles** | `database/migrations/2024_01_02_054837_add_columns_to_articles_table.php` | `meta_title`, etc. |
| **Area Wise (plugin)** | `database/migrations/2026_05_14_080000_create_area_listing_tables.php` | `area_listing_areas` / `area_listing_sub_areas`: `slug`, `seo_title`, `seo_description` |
| **Slugs (core)** | `propertys.slug_id`, `projects.slug_id`, `categories.slug_id`, `articles.slug_id`, `customers` (agents), `custom_pages.slug_id` | Used for public URLs |

**Not found:** dedicated `canonical`, `robots`, or `og_*` DB columns on entities. OG/canonical are handled on the frontend from meta fields + env.

**System setting:** `system_setting('seo_settings')` — when `1`, property admin forms treat meta title as required (`web-fix/property-edit.blade.php`).

### 1.2 Admin panel — what admins can edit

#### A. SEO Settings screen (global static pages)

- **Controller:** `app/Http/Controllers/SeoSettingsController.php`
- **Routes:** `routes/web.php` — `Route::resource('seo_settings', ...)`, permission `has_permissions('read|create|update', 'seo_settings')`
- **View:** `resources/views/seo_settings/index.blade.php`
- **Editable per page row:** meta title, meta description, keywords, OG image, JSON-LD `schema_markup`
- **Page keys** (hardcoded list + dynamic custom pages):

```
homepage, all-categories, about-us, articles, chat, contact-us,
featured-properties, properties-on-map, most-viewed-properties,
most-favorite-properties, privacy-policy, all-properties,
properties-nearby-city, search, subscription-plan, terms-and-condition,
profile, user-register, all-agents, agent-details, faqs,
custom-page/{slug}
```

#### B. Per-listing SEO (properties / projects)

- **Admin blades:** `web-fix/property-edit.blade.php`, `property-create-compare.blade.php`, `project-edit-server.blade.php`
- **Fields:** `meta_title`, `meta_description`, `meta_keywords`, `meta_image`
- **AI assist:** `POST gemini/generate-meta` → `GeminiAIController::generateMetaDetails()` (`routes/web.php`, `routes/api.php`)

#### C. Agent / customer web forms

- `web-fix/AddProperty.jsx`, `EditProperty.jsx`, `AddProject.jsx`, `EditProject.jsx` — tab `seoSettings` posts `meta_title`, `meta_description`, `meta_keywords`, `meta_image`

#### D. Area Wise SEO columns

- **DB columns exist** (`seo_title`, `seo_description` on areas/sub-areas)
- **Admin UI:** no matches for `seo_title` / `seo_description` under `app/Plugins/AreaListing/views/` on server — **not admin-editable today** via Area Manager UI

### 1.3 Slug generation

- **Helper:** `app/Helpers/custom_helper.php` — `generateUniqueSlug()`, `generateSlug()`, `unicodeSlug()` (uses `Str::slug`)
- **Property create/update:** `PropertController.php` — `slug_id = $request->slug ?? generateUniqueSlug($request->title, 1)`; validated `unique:propertys,slug_id`
- **Admin UI:** slug field + AJAX to `route('property.generate-slug')` (`web-fix/property-edit.blade.php`)
- **On title change:** slug is **not** auto-updated unless admin leaves slug empty or regenerates manually
- **Redirect handling:** **none found** — no `old_slug`, 301 table, or redirect logic in `PropertController` / `Property` model when `slug_id` changes

### 1.4 Sitemap (backend)

- **No Laravel sitemap** — no `spatie/laravel-sitemap`, `artesaos/seotools`, or artisan sitemap command in backend
- Sitemap is entirely a **Next.js** concern (see §2)

### 1.5 SEO-related Composer packages

- **No dedicated SEO packages** in `composer.json` (grep for sitemap/seotools returned empty)
- SEO is custom: `SeoSettings` model, `ContentApiController::get_seo_settings`, Gemini meta generation

### 1.6 Public API for SEO

- **`GET /api/get_seo_settings`** — `app/Http/Controllers/Api/ContentApiController.php`
  - `?page=search` filters by page; **no `page` param defaults to `homepage`**
- **`GET /api/get_property?slug_id=…&with_seo=1`** — returns `meta_title`, `meta_description`, `meta_keywords`, `meta_image`; **`schema_markup` is null** on properties (verified for `home-for-rent`)

---

## 2. Existing SEO Features (Frontend — Next.js)

### 2.1 Router and version

| Item | Value |
|------|--------|
| **Router** | **Pages Router** (`pages/`, no `app/` directory) |
| **Next.js** | `^14.2.32` (`/www/wwwroot/homes.sukoon.group/package.json`) |
| **App name** | `ebroker` v1.4.0 |
| **SEO gate** | `NEXT_PUBLIC_SEO="true"` in `.env` — when false, `getServerSideProps` SEO loaders are `null` |

### 2.2 Meta tags — how they're set

**Central component:** `src/components/meta/MetaData.jsx`

- Uses **`next/head`** (not `next-seo`, not App Router `generateMetadata`)
- Sets: `<title>`, description, keywords, author, robots (default `index, follow`), OG tags, Twitter cards, **canonical**, theme-color, PWA links, **JSON-LD** script
- Canonical / OG URL: built with `?lang={code}` from router query
- Fallbacks: `NEXT_PUBLIC_META_TITLE`, `NEXT_PUBLIC_META_DESCRIPTION`, `NEXT_PUBLIC_META_KEYWORD`

**~57 page files** import `MetaData` under `pages/` (most public routes).

**Pages missing `MetaData` (examples):**

- `pages/404.jsx` — client-only, no meta/robots
- Dashboard / authenticated flows may have minimal meta (e.g. `login` uses `<MetaData title="Login" />` only)

**`noindex` usage:**

- `pages/property-detail-preview/[slug]/index.jsx` — `robots="noindex, nofollow"` only explicit case found

### 2.3 Rendering mode by page type

| Page type | Path | Meta (head) | Main content | Notes |
|-----------|------|-------------|--------------|-------|
| **Home** | `pages/index.jsx` | **SSR** (`getServerSideProps` if `NEXT_PUBLIC_SEO`) | Client-heavy `HomePage` | Fetches `get_seo_settings` (defaults homepage) |
| **Property detail** | `pages/property-details/[slug]/index.jsx` | **SSR** — API `get_property?with_seo=1` | **`dynamic(..., { ssr: false })`** | Crawlers get meta in HTML; **body is CSR** |
| **Property listings** | `pages/properties/index.jsx`, `pages/properties/[slug]/index.jsx`, `pages/properties/city/[slug]/index.jsx` | **SSR** meta from `get_seo_settings?page=…` | Listing components (mostly client) | City page title can fall back to generated string |
| **Search (generic)** | `pages/search/index.jsx` | **SSR** — `page=search` | `SearchPage` (client) | |
| **Search (location)** | `pages/search/[citySlug]/index.jsx`, `…/[areaSlug]/…`, `…/[subAreaSlug]/…` | **SSR** — same `page=search` + **location suffix in title only** | `SearchPage` | **Does not use** Area Wise `seo_title` from API |
| **Projects** | `pages/project-details/[slug]/index.jsx` | SSR pattern similar to property | | |
| **Articles** | `pages/article-details/[slug]/index.jsx` | SSR + meta | | |
| **Agents** | `pages/agent-details/[slug]/index.jsx` | SSR + meta | | |
| **"All" hubs** | `pages/all/[slug]/index.jsx` | SSR meta | **`ListingsPage` with `ssr: false`** | |
| **Sitemap** | `pages/sitemap.xml.js` (restored by build script) | **SSR** XML response | N/A | |

**No ISR / `getStaticProps`** found on main SEO pages — pattern is **SSR for meta when `NEXT_PUBLIC_SEO=true`**, client render for UI.

### 2.4 robots.txt and sitemap.xml

| Asset | Path | Status |
|-------|------|--------|
| **robots.txt** | `public/robots.txt` | **Stale/wrong** — points to `https://e-broker-nextjs.vercel.app/sitemap.xml`, not `homes.sukoon.group` |
| **Sitemap (dynamic)** | `pages/sitemap.xml.js` + `scripts/setup-sitemap.js` | Active (`NEXT_PUBLIC_SEO=true`) — build removes static `public/sitemap.xml` to avoid conflict |
| **Sitemap generator** | `scripts/sitemap-generator.js` | Static routes + dynamic APIs (properties, projects, articles, agents) |
| **Live sitemap** | `https://homes.sukoon.group/sitemap.xml` | **~23 URLs** (very low vs inventory); includes `/search/?lang=en` but **not** `/search/barmer/baldev-nagar/…` |
| **XSL** | `public/sitemap.xsl` (referenced) | Browser-friendly sitemap styling |

`next.config.js` — rewrites exist; **no robots/sitemap rewrites** found in grep.

### 2.5 JSON-LD / structured data

| Source | File | Type |
|--------|------|------|
| Admin SEO settings | `schema_markup` textarea → API → `MetaData` | Per-page custom JSON (Organization, WebPage, etc.) |
| Default fallback | `src/utils/helperFunction.js` — `getDefaultSchemaMarkup()` | `Organization` + `SearchAction` |
| Articles | `src/components/cards/ArticleCardListView.jsx` | `itemType="https://schema.org/BlogPosting"` (microdata) |
| Properties | API | **`schema_markup: null`** — no `RealEstateListing` / `Product` JSON-LD on detail pages |

### 2.6 Canonical and noindex

- **Canonical:** `MetaData.jsx` — `<link rel="canonical" href={…}>`; defaults to OG URL with `?lang=`
- **hreflang:** sitemap generator adds `xhtml:link` alternates per language
- **noindex:** only preview route; login/dashboard/agent areas not systematically noindexed

### 2.7 Images

- **`next/image`** via `src/components/image-with-placeholder/ImageWithPlaceholder.jsx`
- Remote optimization limited to `admin-homes.sukoon.group` URLs; skips optimizer for paths with spaces, `/public/`, local paths
- **Alt text:** from props (`property.title`, `parameter.name`, `"404"`, etc.) — **not** a centralized SEO alt strategy
- OG images: entity `meta_image` / SEO settings image / favicon default

---

## 3. URL Structure (Current)

### 3.1 Route patterns (live examples)

| Type | Pattern | Example |
|------|---------|---------|
| **Property detail** | `/property-details/{slug_id}/?lang=en` | `https://homes.sukoon.group/property-details/home-for-rent/?lang=en` |
| **Project detail** | `/project-details/{slug_id}/?lang=en` | |
| **Article** | `/article-details/{slug_id}/?lang=en` | |
| **Agent** | `/agent-details/{slug_id}/?lang=en` | |
| **All properties** | `/properties/?lang=en` | |
| **Featured / filtered listing** | `/properties/{slug}/?lang=en` | e.g. `featured-properties`, `most-viewed-properties` |
| **City listing (legacy)** | `/properties/city/{slug}/?lang=en` | Slug from city name |
| **Category listing** | `/properties/category/{slug}/?lang=en` | |
| **Search (base)** | `/search/?lang=en` | |
| **Search (Area Wise paths)** | `/search/{citySlug}/` → `/search/{citySlug}/{areaSlug}/` → `…/{subAreaSlug}/` | Built by `src/utils/locationSearchUrl.js` — slugs from **display names** (`generateSlug`), not necessarily `area_listing_areas.slug` |
| **Home** | `/?lang=en` | |
| **Custom CMS pages** | `/more-pages/[...slug]/` | |
| **Compare** | `/compare-properties/{slug}/` | |

### 3.2 Area Wise locations as crawlable pages

- **Yes — as search URL segments**, not dedicated landing pages:
  - `/search/barmer/`, `/search/barmer/baldev-nagar/`, `/search/barmer/baldev-nagar/gali-wala/` (pattern)
- **Implemented in:** `pages/search/[citySlug]/index.jsx` (+ nested area/sub-area routes)
- **Not in sitemap** as individual location URLs (only `/search/`)
- **Area `seo_title` / `seo_description` in DB are not wired** to frontend meta for those URLs
- **State** is not a URL segment — only city → area → sub-area

---

## 4. Admin Manageability (Current)

### 4.1 What admins can change without code

| Capability | Where | Editable fields |
|------------|-------|-----------------|
| **Static page SEO** | Admin → SEO Settings | title, description, keywords, image, schema_markup per page key |
| **Property SEO** | Admin property CRUD + agent web form | meta_title, meta_description, meta_keywords, meta_image |
| **Project SEO** | Admin project CRUD + agent form | Same meta_* fields |
| **Category / article SEO** | Admin category/article screens | meta_* (per migrations) |
| **Property slug** | Admin property form | `slug` / `slug_id` (manual or generate from title) |
| **Gemini meta generation** | Admin/API when enabled | AI-generated title/description/keywords |
| **Global defaults (frontend)** | Server `.env` on Next host | `NEXT_PUBLIC_META_*`, `NEXT_PUBLIC_WEB_URL`, `NEXT_PUBLIC_SEO` |
| **Homepage brand copy** | `src/utils/en.json` / `hi.json` (deployed) | `homeBrandName`, taglines (content, not classic SEO meta) |
| **Area Wise SEO** | DB only | **Not exposed** in Area Manager UI |

### 4.2 Plugin structure (for a future SEO plugin)

| Plugin | Registration pattern | Admin | Permissions |
|--------|---------------------|-------|-------------|
| **AreaListing** | `routes/web.php` conditional `require app/Plugins/AreaListing/routes/web.php`; views via `AppServiceProvider::loadViewsFrom(..., 'area-listing')` | `AreaListingAdminController` under `/area-listing` | `auth` + `checkLogin` middleware; **no** `has_permissions()` in controller grep |
| **NearbyPlaces** | Same pattern: `require Plugins/NearbyPlaces/routes/web.php` (+ `api.php`) | Plugin controllers under `app/Plugins/NearbyPlaces/` | Plugin-local |
| **OwnerDashboard** | **Dedicated** `OwnerDashboardServiceProvider` — `loadMigrationsFrom`, `loadViewsFrom`, `loadRoutesFrom`, observers, schedule | Tenant/owner dashboards (not classic admin SEO) | Service-based |

**Convention for a new SEO plugin:** follow **AreaListing / NearbyPlaces** (self-contained under `app/Plugins/{Name}/`, routes required from `routes/web.php`, optional provider) or **OwnerDashboard** (full `ServiceProvider` if migrations + schedules needed). Stock eBroker permissions use `has_permissions('read', 'module')` as in `SeoSettingsController`.

---

## 5. Gaps Summary

| Feature | Exists? | Where | Admin-editable? | Notes |
|---------|---------|-------|-----------------|-------|
| Global per-page meta (title/desc/keywords/OG/schema) | ✅ | `seo_settings` table + `SeoSettingsController` | ✅ | ~20 fixed page keys + custom pages |
| Property-level meta | ✅ | `propertys.meta_*` + API `with_seo=1` | ✅ | No `schema_markup` on property rows |
| Project/category/article meta | ✅ | DB + admin | ✅ | |
| Area/sub-area meta | ⚠️ Partial | `area_listing_*`.seo_* columns | ❌ | Columns exist; no admin UI; not used on `/search/...` pages |
| Slug generation | ✅ | `custom_helper.php`, controllers | ✅ (manual) | Unique slug; suffix on collision |
| Slug change → 301 redirect | ❌ | — | — | Old URLs break if slug changed |
| Laravel sitemap | ❌ | — | — | |
| Next.js dynamic sitemap | ✅ | `pages/sitemap.xml.js`, `scripts/sitemap-generator.js` | ⚠️ Env/build | Live sitemap ~23 URLs; missing location URLs |
| robots.txt | ⚠️ Broken | `public/robots.txt` | ❌ | Wrong sitemap domain (Vercel demo) |
| `next/head` meta component | ✅ | `src/components/meta/MetaData.jsx` | ⚠️ | Driven by API + env |
| `next-seo` package | ❌ | — | — | |
| SSR meta tags | ✅ | `getServerSideProps` when `NEXT_PUBLIC_SEO=true` | — | Disabled entirely if env false |
| SSR page content (property detail) | ❌ | `PropertyDetailPage` `ssr: false` | — | SEO head only; weak for full-page crawl |
| Location landing SEO | ⚠️ Partial | `/search/{city}/{area}/…` | ❌ | Generic `search` SEO row + title suffix; no per-area meta |
| Location URLs in sitemap | ❌ | — | — | Only `/search/` |
| Property JSON-LD | ❌ | — | — | `schema_markup` null from API |
| Article structured data | ⚠️ Partial | `BlogPosting` microdata on cards | — | Not verified on detail page |
| Default Organization schema | ✅ | `getDefaultSchemaMarkup()` | ⚠️ | `areaServed: Global` — not Barmer-specific |
| Canonical tags | ✅ | `MetaData.jsx` | — | Lang query param in URL |
| hreflang in sitemap | ✅ | `sitemap-generator.js` | — | |
| noindex for private/dashboard routes | ❌ | — | — | Only preview page |
| Gemini AI meta generation | ✅ | `GeminiAIController` | ✅ (if enabled) | Admin + API |
| SEO composer packages | ❌ | — | — | Custom implementation only |
| OG image per property | ✅ | `meta_image` | ✅ | Falls back to title image |
| Image alt text strategy | ⚠️ Partial | Component props | ❌ | Inconsistent; not from SEO fields |
| Env-based default meta | ✅ | `.env` on Next server | ⚠️ Deploy | Used when API row missing |
| 404 page SEO | ❌ | `pages/404.jsx` | — | No MetaData |

---

## 6. Priority observations (for future work)

1. **Fix `public/robots.txt`** — point sitemap to `https://homes.sukoon.group/sitemap.xml`.
2. **Expand sitemap** — include property/project URLs reliably (only 23 URLs today) and optionally Area Wise `/search/...` paths.
3. **Wire Area Wise `seo_title` / `seo_description`** — admin UI + frontend meta on location search pages.
4. **Property structured data** — add `RealEstateListing` or `Apartment` JSON-LD; optionally store `schema_markup` on properties.
5. **Slug redirects** — 301 table when `slug_id` changes.
6. **Property detail SSR** — remove `ssr: false` on main content for better indexing.
7. **noindex** — dashboard, login, payment, preview, agent portal routes.

---

*Sources: production server `srv1534644`, paths under `/www/wwwroot/admin-homes` and `/www/wwwroot/homes.sukoon.group`, plus local `web-fix/` mirrors.*

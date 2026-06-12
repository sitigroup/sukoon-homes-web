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

*Next: **TASK A4 — Property JSON-LD + SSR content** (pause for human review after)*

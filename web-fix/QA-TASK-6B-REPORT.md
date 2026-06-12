# TASK 6C — Light premium cleanup (2026-05-24)

Applied P0–P2 fixes; deployed to production. Build passed; PM2 restarted.

**Changed files:** `VerificationDataUsageNotice.jsx`, `VerificationLegalPageLayout.jsx`, `VerificationSuccessScreen.jsx`, `TrustVerificationPage.jsx`, `VerificationPackageCard.jsx`, `pages-my-verification-orders.jsx`

**Post-deploy screenshots:** `tv-6c-verification-{375,768,1024}.png`, `tv-6c-tenant-{375,768,1024}.png`, `tv-6c-tenant-details-375.png`

---

# TASK 6B — Trust Verification UI QA Report

**Date:** 2026-05-24  
**Environment:** https://homes.sukoon.group (production)  
**Scope:** QA only — no TASK 6C changes applied in this pass.

## Build status

| Check | Result |
|-------|--------|
| All 8 TV routes HTTP | **200** (HEAD) |
| Next.js `BUILD_ID` (server) | `zgu1Q-Pin2wJLqQhFAtOX` |
| PM2 `homes-sukoon` | **online** |
| Browser console (all pages visited) | **No app errors** — only CursorBrowser tooling warnings |

## Screenshots captured

Saved during QA under Cursor browser screenshots (`tv-qa-*.png`):

| File | Page / viewport |
|------|-----------------|
| `tv-qa-01-verification-hub-375.png` | `/verification/?lang=en` — 375px |
| `tv-qa-01-verification-hub-1024.png` | `/verification/?lang=en` — 1024px |
| `tv-qa-01-verification-hub-768.png` | `/verification/?lang=en` — 768px |
| `tv-qa-02-tenant-barmer-375.png` | Tenant wizard — 375px |
| `tv-qa-02-tenant-barmer-320.png` | Tenant wizard — 320px |
| `tv-qa-02-tenant-barmer-768.png` | Tenant wizard — 768px |
| `tv-qa-02b-tenant-details-375.png` | Tenant step 2 (forms/uploads) — 375px |
| `tv-qa-03-owner-barmer-375.png` | Owner wizard — 375px |
| `tv-qa-04-my-orders-375.png` | My orders (logged out) — 375px |
| `tv-qa-05-ui-demo-375.png` | UI demo — 375px |
| `tv-qa-06-verification-terms-375.png` | Terms — 375px |
| `tv-qa-07-verification-privacy-375.png` | Privacy — 375px |
| `tv-qa-08-verification-refund-375.png` | Refund — 375px |

## Page-by-page summary

| Page | Layout | Mobile | Console | Notes |
|------|--------|--------|---------|-------|
| `/verification/?lang=en` | OK | OK 320–1024 | Clean | Light premium hub; ~3–5s initial loader before content |
| `/tenant-verification-in-barmer/?lang=en` | OK with caveats | OK | Clean | Package + wizard styled; see issues below |
| `/owner-verification-in-barmer/?lang=en` | OK | OK | Clean | Mirrors tenant flow |
| `/my-verification-orders/?lang=en` | OK | OK | Clean | Logged-out empty state works; large gap above site footer |
| `/trust-verification-ui-demo/?lang=en` | OK | OK | Clean | Badges, cards, stepper visible |
| `/verification-terms/` | OK | OK | Clean | Legal cards styled |
| `/verification-privacy/` | OK | OK | Clean | Legal cards styled |
| `/verification-refund-policy/` | OK | OK | Clean | Legal cards styled |

**Not tested in browser (requires login):** My orders with real order rows, timeline, document upload on existing orders, pay/review steps end-to-end.

## UI issues (ordered by severity)

### Medium

1. **Wizard desktop/tablet dead space (768–1024px)** — `TrustVerificationPage.jsx` uses `lg:grid-cols-5` (3+2). Below `lg`, package cards and stepper stack, but between **768–1023px** package cards stay **2-column** while the step panel is full-width below, leaving a wide empty band on the right in some viewports.
2. **Data-use notice low contrast** — `VerificationDataUsageNotice.jsx` still uses dark-theme tokens (`text-[#C9C9C9]`, `border-white/[0.08]`). Body text is hard to read on light `#F8F9FA` backgrounds (details step).
3. **Legal footer dividers invisible on light** — `VerificationLegalPageLayout.jsx` uses `border-white/[0.08]` above legal links; separator does not show on white/light gray.
4. **Initial global loader** — All TV routes show site-wide teal spinner for several seconds before TV content (APIs return 200). Not broken, but feels like a blank page on slow networks.

### Low

5. **“For tenants” toggle truncation** — On narrow widths the second role toggle label can clip (`For tenan…`) in wizard hero (`TrustVerificationPage.jsx` ~300–380px).
6. **Package feature line clamping** — Long feature labels (e.g. “Previous landlord reference”) can clip at card edge on tight 2-column layouts.
7. **My orders logged-out vertical gap** — `VerificationEmptyState` + short content leaves a tall white area before the global footer (sitewide layout, not TV-only).
8. **Duplicate H1 on legal pages** — `NewBreadcrumb` title + in-shell `<h1>` (SEO/a11y nit).
9. **Success screen footer** — `VerificationSuccessScreen.jsx` still has `border-white/[0.08]` (not visible in this QA pass without completing an order).

### None observed

- No JavaScript errors in console on tested routes.
- No 404/500 on listed URLs.
- Forms, file inputs, package cards, consent checkbox (code review), timeline (demo), and legal pages render with light premium styling on live site.

## TASK 6C — files that still need light-theme token cleanup

Most of the plugin is already light (`trustVerificationPremium.module.css`, `trustVerificationTheme.js`, hub, cards, consent). **Remaining dark-theme remnants to fix in 6C:**

| Priority | File | Why |
|----------|------|-----|
| P0 | `ui/VerificationDataUsageNotice.jsx` | Dark border + `#C9C9C9` text on light UI |
| P0 | `VerificationLegalPageLayout.jsx` | `border-white/[0.08]` footer rule |
| P1 | `ui/VerificationSuccessScreen.jsx` | `border-white/[0.08]` on success footer |
| P2 | `TrustVerificationPage.jsx` | Responsive grid / empty space 768–1023px |
| P2 | `MyVerificationOrdersLink.jsx` | Stock `brandColor` / `brandBorder` (minor vs premium) |
| P2 | `VerifyOwnerPropertyCard.jsx` | Stock tokens if shown on property pages |
| — | `VerificationDocumentFields.jsx`, `OrderDocumentsPanel.jsx` | Already support `variant="premium"`; non-premium fallbacks use stock classes (only if reused outside TV) |

**Already light — verify only in 6C regression pass:**  
`ui/trustVerificationPremium.module.css`, `ui/trustVerificationTheme.js`, `ui/VerificationHubPremium.jsx`, `ui/VerificationPackageCard.jsx`, `ui/VerificationConsentCheckbox.jsx`, `ui/VerificationTimeline.jsx`, `ui/VerificationProgressStepper.jsx`, `ui/VerificationStatusBadge.jsx`, `pages-verification-*.jsx`, `pages-my-verification-orders.jsx`, `pages-trust-verification-ui-demo.jsx`.

## Recommended 6C order

1. Fix `VerificationDataUsageNotice` + legal/success borders (quick token swap).
2. Tweak wizard grid breakpoints in `TrustVerificationPage.jsx` (`md:grid-cols-1` for packages until `lg`, or single column 768–1023).
3. Full regression screenshot pass (same matrix as above) after deploy.

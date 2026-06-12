# Task 16C — Sukoon Trust UI Premium Upgrade

Frontend-only visual upgrade. Same APIs and visibility rules as Task 16B.

## Features

| Feature | Implementation |
|---------|----------------|
| Trust ring | `SukoonTrustRing.jsx` — SVG circular progress, center `n / 100` |
| Trust tier | `trustTierUtils.js` + `SukoonTrustTierPill.jsx` — score bands → pill label |
| Breakdown cards | `SukoonTrustBreakdownCards.jsx` — grid of factor cards with pass/pending |
| Badge redesign | `SukoonTrustBadges.jsx` — `variant="card"` (icon, title, description) or `chip` for compact |
| Tier colors | 0–30 gray · 31–60 blue · 61–85 graphite · 86–100 black |

## Tier mapping

| Score | Tier | Accent |
|-------|------|--------|
| 0–30 | Basic | `#6b7280` |
| 31–60 | Verified | `#2563eb` |
| 61–85 | Trusted | `#374151` |
| 86–100 | Elite Trusted | `#111827` |

## Changed files

- `ui/trustTierUtils.js` (new)
- `ui/SukoonTrustRing.jsx` (new)
- `ui/SukoonTrustTierPill.jsx` (new)
- `ui/SukoonTrustBreakdownCards.jsx` (new)
- `ui/SukoonTrustCard.jsx`
- `ui/SukoonTrustBadges.jsx`
- `ui/SukoonOwnerTrustSection.jsx`
- `ui/SukoonTrustedOwnerBadge.jsx`
- `ui/DropdownTrustSnippet.jsx`
- `ui/trustVerificationPremium.module.css`

## Deploy

Copy `web-fix/plugins/trust-verification/ui/*` to homes `src/plugins/trust-verification/ui/`, then `npm run build` and restart PM2.

## QA

- [ ] Ring animates stroke by score; reads `n / 100` in screen readers
- [ ] Tier pill matches score band colors on profile, agent card, dropdown, property overlay
- [ ] Breakdown cards responsive: 1 col mobile, multi-col tablet+
- [ ] Badge cards show title + description; chips on compact dashboard card
- [ ] Property overlay: mini ring + tier, no layout break on 320px
- [ ] No API or backend changes required

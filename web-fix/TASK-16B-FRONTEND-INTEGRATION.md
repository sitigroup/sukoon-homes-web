# Task 16B — Trust Score Frontend Integration

Frontend-only wiring of Sukoon Trust components into the live Homes Next.js app (`homes.sukoon.group`). No backend, DB, or trust calculation changes.

## Changed files

### Plugin (`src/plugins/trust-verification/`)

| File | Role |
|------|------|
| `trustVerificationApi.js` | `getTrustVerificationTrustProfile`, `getPublicTrustProfile` |
| `TrustProfileSection.jsx` | My profile trust block (optional `compact`) |
| `ui/SukoonTrustCard.jsx` | Score, breakdown, badges |
| `ui/SukoonTrustBadges.jsx` | Badge chips (react-icons) |
| `ui/SukoonTrustedOwnerBadge.jsx` | Property card overlay |
| `ui/SukoonOwnerTrustSection.jsx` | Property detail owner trust + verification summary |
| `ui/SukoonPublicTrustProfile.jsx` | Public agent trust card |
| `ui/DropdownTrustSnippet.jsx` | Account dropdown trust line |
| `ui/trustVerificationPremium.module.css` | Scoped trust styles (no global override) |

### Homes components (`src/components/`)

| File | Integration |
|------|-------------|
| `cards/PropertyVerticalCard.jsx` | `SukoonTrustedOwnerBadge` on image (`added_by`) |
| `cards/PropertyHorizontalCard.jsx` | Same |
| `owner-details-card/OwnerDetailsCard.jsx` | `SukoonOwnerTrustSection` |
| `user/UserProfile.jsx` | `TrustProfileSection` |
| `agent/AgentProfile.jsx` | `TrustProfileSection` |
| `cards/AgentHorizontalCard.jsx` | `SukoonPublicTrustProfile` (`id`) |
| `dashboard/ProfileCard.jsx` | Compact `TrustProfileSection` |
| `reusable-components/UserDropDown.jsx` | `DropdownTrustSnippet` |

## Visibility rules

- **Public surfaces** (cards, owner sidebar, agent public card): `GET /api/trust-verification/public-trust/{customerId}` — only when global public trust is enabled and customer `public_visible` is true (API returns `data: null` otherwise; UI renders nothing).
- **Account surfaces** (my profile, dropdown, dashboard): `GET /api/trust-verification/trust-profile` (authenticated).

## Deploy

From server (after syncing `web-fix`):

```bash
bash /path/to/web-fix/deploy-task-16b-trust-frontend.sh
```

Or copy files manually under `/www/wwwroot/homes.sukoon.group`, then:

```bash
cd /www/wwwroot/homes.sukoon.group && npm run build && pm2 restart homes-sukoon
```

## QA checklist

### Property cards
- [ ] Listing with owner who has public trust + score shows overlay: Trusted Owner (if eligible) + `Trust {n}`
- [ ] Owner with public trust disabled shows **no** overlay
- [ ] Vertical and horizontal cards on mobile / tablet / desktop — no overlap with category chip or favourite button
- [ ] Clicking card still navigates; badge `stopPropagation` does not break like/favourite

### Property details
- [ ] Owner sidebar shows Sukoon Trust score, badges, Identity / Reference / Police summary when public trust available
- [ ] No section when public API returns null
- [ ] Layout intact at 320px, 768px, 1280px

### My profile (`/user/profile`)
- [ ] Trust section: score, badge collection, breakdown list
- [ ] Empty state when no score yet
- [ ] Agent profile (`/agent/profile`) same section

### Agent public (`/agent-details/{slug}`)
- [ ] Public trust card under agent header when enabled
- [ ] Hidden when public disabled

### Account dropdown
- [ ] Logged-in user with score sees `Sukoon Trust {n}` under email
- [ ] No line when no score

### Dashboard
- [ ] Compact trust card in profile hero when user has score
- [ ] No empty box when compact + no trust

### Regression
- [ ] No global CSS changes outside trust module
- [ ] Verification orders / hub pages unchanged
- [ ] Build completes without errors

## Screenshots (capture after deploy)

1. Property grid — card with trust overlay  
2. Property detail — owner trust section  
3. `/user/profile` — Sukoon Trust section  
4. `/agent-details/...` — public trust on agent card  
5. Header dropdown — trust snippet  
6. Mobile (375px) — property card + detail sidebar  

## Rollback

Restore previous component copies from backup or git; remove new `ui/Sukoon*.jsx` imports; rebuild Next.js.

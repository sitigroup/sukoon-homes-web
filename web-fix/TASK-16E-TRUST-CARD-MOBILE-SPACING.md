# Task 16E — Trust Card Mobile Spacing Fix

## Issue
On mobile property detail, large blank gap between trust description and "Verification badges".

## Root cause
`.trustCardHeroText` used `flex: 1 1 12rem`. In column layout (`flex-direction: column`), flex-basis `12rem` forced the text block to ~192px min height below short copy — the large blank band above “Verification badges”.

## Fix (frontend only)
**File:** `web-fix/plugins/trust-verification/ui/trustVerificationPremium.module.css`

- Hero text: `flex: 1 1 auto`, `min-height: 0` (desktop row); mobile `flex: 0 0 auto`
- Hero: no extra `margin-bottom` (spacing from subcopy + section only)
- Subcopy: `margin-bottom: 18px` max (desktop); `0` on mobile (section `margin-top: 16px` handles gap)
- Section divider: `margin-top: 16px`, `padding: 0`, `border-top`; title `margin-top: 16px` (12px mobile)
- Mobile (`max-width: 639px`): column hero, centered ring, tightened gaps

**File:** `SukoonOwnerTrustSection.jsx` — no JSX change; `.ownerTrustSection .trustCard { margin-bottom: 0 }` only.

## Deploy
```bash
scp web-fix/plugins/trust-verification/ui/trustVerificationPremium.module.css srv1534644:/www/wwwroot/homes.sukoon.group/src/plugins/trust-verification/ui/
cd /www/wwwroot/homes.sukoon.group && npm run build && pm2 restart homes-sukoon
```

## QA
- **URL:** https://homes.sukoon.group/property-details/new?lang=en (owner customer #15, trust score 100)
- **Screenshot:** `web-fix/task-16e-trust-card-375px.png` (375×812 viewport)
- Intro → divider → badges: no ~192px flex dead space; ~16px + border + 12px to heading on mobile
- Trust ring centered in column layout on mobile
- Desktop: side-by-side ring + text row unchanged

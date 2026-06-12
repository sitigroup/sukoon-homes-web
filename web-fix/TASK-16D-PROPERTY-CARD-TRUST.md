# Task 16D — Trust UI Simplification + Property Cards

Frontend only. Score **breakdown** (Identity +20, Address +15, etc.) is **admin-only** — never shown on Homes.

## Public frontend shows

- Trust score (ring on profile / detail / dashboard compact)
- Trust tier pill (Basic · Verified · Trusted · Elite Trusted)
- Verification badges (name + description cards)
- Property card pill only (no ring): `✓ Trusted Owner · Trust 100` / mobile `✓ Trusted · 100`

## Does NOT show (public)

- Score breakdown grid or point factors (+20 pts, etc.)
- `sanitizePublicTrust()` strips `breakdown` from API payloads before render
- `SukoonTrustBreakdownCards` is a no-op on Homes (admin breakdown stays in Laravel admin only)

## Property card placement (16D final)

1. Image  
2. Title  
3. Location + **fixed 24px trust slot** (badge or empty — equal card heights)  
4. Specs  
5. Price  

Listing label only: **`✓ Trusted Owner`** or **`✓ Trusted Tenant`** — no score, no Elite tier.

Style: white bg, `#111827` border, 24px height, 6×10px padding, 11–12px font.

## Surfaces

| Surface | UI |
|---------|-----|
| Property card | Inline black pill after location |
| Property detail owner | `SukoonTrustCard` title "Sukoon Trust" |
| My profile | Full card + badge collection |
| Dashboard hero | Compact card |
| Admin | Unchanged (full breakdown in Laravel views) |

## Hydration

- `SukoonTrustedOwnerBadge`: hydrated + `startTransition`, `dynamic(..., { ssr: false })` on cards

## Files

- `ui/SukoonTrustCard.jsx` — no breakdown section
- `ui/trustTierUtils.js` — B/W tier colors
- `ui/SukoonOwnerTrustSection.jsx`, `TrustProfileSection.jsx`
- `ui/SukoonTrustedOwnerBadge.jsx`
- Property card components + CSS `.propertyTrust*`

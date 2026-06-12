# Task 18D — Tenant Reliability Explainability + Premium Trust UX

**Scope:** `web-fix/plugins/trust-verification/ui/` only. No API or core changes.

## UI changes

| Part | Implementation |
|------|----------------|
| Reliability Factors | Verified checks only → positive labels (identity, documents, reference, police, verification completed) |
| Privacy notice | Static “Not considered” list (religion, caste, chat, PII, income) — 13px `#6B7280` |
| Badge | High (green) / Medium (amber) / Verification Pending (graphite) — **no % or numeric score** |
| Tooltip | “What does this mean?” → Sukoon Homes verification activities copy |
| Premium shell | White card, soft shadow, graphite text, thin gold accent bar |

## Files changed

- `ui/tenantReliabilityUx.js` (new)
- `ui/SukoonTenantReliabilityCard.jsx`
- `ui/trustVerificationPremium.module.css`
- `ui/index.js` (exports)

## Visibility (unchanged)

Still only: Interested Users, Chat, Booking (`TenantApplicantReliabilityPanel`).

Not on property detail, search, map, or public cards.

## Deploy

```bash
bash web-fix/deploy-task-18c-frontend-wiring.sh
```

(Copies all `ui/*.js` / `*.jsx` + CSS.)

## QA checklist

- [ ] Interested Users → View → badge + factors + privacy + tooltip
- [ ] No `%`, trust score, or tier labels (Basic/Trusted) on card
- [ ] Property detail — no tenant reliability card
- [ ] Mobile 375px — readable, no overflow
- [ ] API response unchanged (grep network tab)

# Task 18C — Tenant Reliability Frontend Flow Wiring

**Scope:** Frontend only. No backend/API changes.

## Wired surfaces

| Flow | Component | When card shows |
|------|-----------|-----------------|
| Interested users (owner screening) | `UserInterested.jsx` | Owner clicks **View** on a row → panel above table |
| Inquiry / messages | `AgentChat.jsx` | Active chat with another user (`user_id` ≠ logged-in id) — `/user/chat` and `/agent/chat` |
| Booking / appointments | `AppointmentDetailsCard.jsx` | `is_agent={true}` expanded appointment (agent/owner viewing applicant) |

## Not wired (by design)

| Surface | Reason |
|---------|--------|
| Public `PropertyDetails` / `OwnerDetailsCard` | Shows **owner** trust only; no `tenantCustomerId` |
| `CustomPropertyDetailsPage` | No `OwnerDetailsCard`; contact sidebar unchanged |
| Tenant viewing own profile / appointments as `is_user` | Viewer is applicant, not screener |

## New helper

`plugins/trust-verification/ui/TenantApplicantReliabilityPanel.jsx` — resolves `id` / `user_id` / `customer_id` and renders `SukoonTenantReliabilitySection`.

## Deploy

```bash
bash web-fix/deploy-task-18c-frontend-wiring.sh
```

Or copy `web-fix/homes-frontend` + `web-fix/plugins/trust-verification/ui` to homes `src/`, then `npm run build` + PM2 restart.

## QA

- [ ] `/user/interested/{propertySlug}` — View → card (Basic/Premium, %, badges)
- [ ] `/user/chat?propertyId=&userId=` — card under header when chatting with tenant
- [ ] Agent bookings — expand row → card next to contact block
- [ ] `/property-details/{slug}` — **no** tenant reliability card
- [ ] Mobile 375px — card full width, no overflow
- [ ] API null customer — card hidden (no error)

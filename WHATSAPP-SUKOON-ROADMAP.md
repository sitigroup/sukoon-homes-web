# Sukoon WhatsApp Roadmap

**Final document:** `WHATSAPP-SUKOON-ROADMAP.md` (repo root)  
**Version:** 1.4 · **Last updated:** 2026-06-03  
**Audience:** Sukoon Homes internal dev/ops — not a reusable product for third parties.

Internal WhatsApp for **Sukoon Homes only** — one Meta number, tied to rental agreements, maintenance, rent, and renewals. Not a third-party product (no WATI-style SaaS, flow builder, or cold marketing).

**Stack:** Laravel plugin `app/Plugins/Whatsapp` → Meta Cloud API → queue `whatsapp` → shared admin inbox.

**Servers:** Backend `admin-homes` (Laravel) · Customer/agent app `homes.sukoon.group` (Next.js) — WhatsApp admin UI lives in the plugin on admin-homes.

---

## SUKOON-HOMES-RULES (always follow)

Every WhatsApp (and related) change must comply with these rules. No exceptions without explicit approval.

### Architecture and code boundaries

| Rule | Detail |
|------|--------|
| **Plugin-only** | All WhatsApp code under `app/Plugins/Whatsapp/`. **No WRTeam core edits** (no changes to vendor `app/Http`, core models, core routes, WRTeam packages). |
| **Wire from other plugins** | Other plugins call `\Whatsapp::notify()` or `WhatsappPlatformEventService` — never duplicate Meta HTTP in Maintenance, RA, RentPayment, etc. |
| **Never block core flows** | WhatsApp send failures must `try/catch`, log, and return gracefully. Agreement sign, move-in, MR resolve, rent payment must succeed even if Meta is down. |
| **Queue** | Outbound sends via `SendWhatsappTemplateJob` on queue `whatsapp`. Ensure worker runs in production. |
| **Meta direct** | Use Meta Cloud API (`MetaGraphClient`), not WATI or other BSP middleware. |
| **System events in code** | New platform triggers = PHP in plugin + `WhatsappEventCatalog` (`wired: true`). Admin only maps template, language, enabled — not new auto-triggers without code. |
| **Migrations** | Plugin migrations only: `app/Plugins/Whatsapp/database/migrations`. Run with `--path=app/Plugins/Whatsapp/database/migrations`. |

### Admin UI and brand

| Rule | Detail |
|------|--------|
| **Mazer theme** | Match existing admin layout (`layouts.main`), cards, forms — consistent with rest of admin-homes. |
| **Brand colors** | Graphite `#1F2937` (primary buttons, headers), gold `#B89A4A` (accents, secondary CTAs), white/light grey surfaces. |
| **No emoji in code** | No emoji in PHP, Blade, JS, or lang files. UI text only. |
| **i18n** | All user-visible strings via `__('whatsapp::whatsapp.key')` in **en** and **hi** (`resources/lang/en/whatsapp.php`, `resources/lang/hi/whatsapp.php`). |
| **Permissions** | Guard with `has_permissions('inbox', 'whatsapp')`, `has_permissions('events', 'whatsapp')`, `has_permissions('templates', 'whatsapp')` as already used. |
| **Mobile responsive** | Inbox and admin WhatsApp pages must work on tablet/phone (list ↔ chat toggle, no broken composer). |

### Messages and Meta

| Rule | Detail |
|------|--------|
| **Templates outside 24h** | Free-form text only inside customer service window; otherwise approved **template** messages only. |
| **Variable count** | BODY params sent to Meta must match template `components_json` exactly (N placeholders = N values). No `array_filter` that drops empty slots. |
| **Template categories** | Sync/use UTILITY and AUTHENTICATION templates for operational messages — not marketing spam. |
| **Phone format** | E.164-style digits (India: prefix `91` when stored as 10-digit local). Use shared normalizer before Meta API. |
| **Opt-in** | Batch/scheduled sends (Phase C) only to existing customers with consent/opt-in recorded — no cold lists. |

### Deploy and ops

| Rule | Detail |
|------|--------|
| **Run as `www`** | On server: `sudo -u www php artisan ...` for migrate, cache, queue smoke tests. |
| **Clear caches** | After plugin/view/lang changes: `php artisan optimize:clear` and `view:clear` if needed. |
| **No secrets in repo** | Tokens and app secrets stay in DB/env on server — never commit credentials. |
| **Plugin deploy scripts** | Prefer focused deploy bundles under repo (`deploy-whatsapp-*`) copying into plugin path — document what was shipped. |
| **Frontend** | Next.js customer/agent UI changes only in `homes.sukoon.group` when needed; WhatsApp **admin** inbox is Blade in plugin (no npm required for inbox unless explicitly adding admin assets). |

### What we are not building

| Do not | Reason |
|--------|--------|
| WATI-style SaaS / flow builder | Sukoon-internal ops only |
| Public WhatsApp API for outsiders | Internal `Whatsapp::notify()` only |
| Cold marketing broadcasts | Compliance + not rental ops |
| Instagram / Facebook inbox | Single WhatsApp channel |
| AI chatbot (until explicitly roadmap-approved) | Canned replies + keywords first |

---

## Design principles

| Build | Do not build |
|-------|----------------|
| Auto templates on platform actions (RA, MR, rent, renewal) | No-code chatbot / flow builder |
| Shared inbox for admin/agents with Sukoon context | Public API for external apps |
| Manual send by mapped event + correct variable count | Marketing broadcasts to cold lists |
| Utility templates (UTILITY / AUTHENTICATION) | Instagram / Facebook inbox |
| `Whatsapp::notify()` from existing plugins | Zapier, multi-WABA SaaS dashboard |

---

## Current baseline (already shipped)

- Meta settings, template sync, event → template mapping (enable/disable)
- Wired events: `agreement_ready`, `vendor_assigned`, `maintenance_resolved`, `rent_reminder`, `owner_payment_request`
- Shared inbox: conversations, reply (24h window), template when window closed, send-by-event
- Queue + audit log; `wa.me` fallback for vendor when API fails
- Dynamic variable fields on manual send (per template BODY param count)

**Catalog reference:** `WhatsappEventCatalog` — system events wired in code; admin maps templates only.

---

## Phase A — Trust and visibility

**Goal:** Production sends work; staff see what was sent and why it failed; inbound messages land in the inbox.

| # | Item | Description |
|---|------|-------------|
| A1 | **Production Meta** | Live WABA, permanent token, real recipient numbers (no test allowlist #131030). Checklist: environment mode, phone quality, template approval. |
| A2 | **Template preview** | Inbox (and optionally events/history) show **rendered** template BODY from `components_json` + stored variables — not only `event_key · RA-…`. |
| A3 | **Inbound webhook health** | Webhook verified; incoming messages create/update `wa_contacts`, `wa_conversations`, `wa_messages`; queue processes `ProcessWhatsappWebhookJob`; 24h window updated. |
| A4 | **Failure dashboard** | Admin view: per event/template — sent, delivered, read, failed counts; drill to recent failures with Meta `error_json` summary. |
| A5 | **Variable validation** | *Conditional.* Only if #132000 (param count mismatch) still occurs after A2 + dynamic manual forms. Server + UI must send exactly N BODY vars. |

### Phase A — acceptance criteria

- [ ] `agreement_ready` (and one other event) delivers to a **production** tenant/owner number successfully.
- [ ] Failed sends show Meta reason in UI (e.g. allowlist, template rejected), not generic “window expired” when `type=template`.
- [ ] Customer sends “Hi” on WhatsApp → row appears in inbox within 5 minutes (webhook + worker running).
- [ ] Admin can see last 7 days: sent vs failed by `event_key` without opening `laravel.log`.
- [ ] Template bubble shows human-readable message text (variables substituted).

### Phase A — suggested order

1. Production Meta (A1)  
2. Template preview (A2) — parallel with A1 once templates synced  
3. Inbound webhook health (A3)  
4. Failure dashboard (A4)  
5. Variable validation (A5) only if still needed  

---

## Phase B0 — Inbox experience (UI only)

**Goal:** Premium Sukoon product feel (HubSpot / Zendesk / Freshchat style) — **no new business logic**, only layout and presentation. Graphite `#1F2937`, gold `#B89A4A`, Mazer-compatible, mobile responsive.

Do **after Phase A**, **before** B1 data wiring (shell first, then real context).

| # | Item | Description |
|---|------|-------------|
| B0.1 | **Three-column layout** | `Conversations \| Chat \| Context` — not two columns. Context column can show placeholders until B1 loads live data. |
| B0.2 | **Conversation cards** | List rows: name (not `#4 · phone`), role, property line, last message preview, relative time, status dot (open/pending/resolved). |
| B0.3 | **Message bubbles** | WhatsApp-style in/out alignment, grouping, soft backgrounds, bubble time, delivery ticks (sent/delivered/read/failed). |
| B0.4 | **Typography and chrome** | Dark graphite header, gold status accents, sticky composer, readable thread spacing. |
| B0.5 | **Inbox filter bar** | Top chips: All, Open, Assigned, Unassigned — plus tag filters when B4 ships (Maintenance, Agreement, Rent, Verification). |
| B0.6 | **Mobile** | List ↔ chat toggle; context collapses to drawer or bottom sheet on small screens. |

**Related UI (same phase or B0+):**

| Item | Notes |
|------|--------|
| **Context panel (layout)** | Right column structure: Customer, Phone, Role, Property, Agreement, Maintenance, Assigned agent, quick links — **B1 fills data**. |
| **Quick action bar** | Buttons above composer (Property, Agreement, MR, etc.) — **B2 canned replies** inserts text; actions open admin URLs or pre-filled snippets. |
| **Templates page cards** | Optional: card UI per template (APPROVED, mapped event, Preview, Test send) instead of heavy tables — admin-only, separate from inbox thread. |

| B0.7 | **Empty states** | Clear copy when there is nothing to show — avoids blank columns that feel broken. Examples: no conversations yet; no conversation selected; no property linked; no open MR for contact; empty thread. en + hi via `__()`. |
| B0.8 | **Skeleton loaders** | Placeholder shimmer for conversation list and context panel while data loads — not plain “Loading…”. Thread can skeleton on first open. |

**Empty state examples (copy direction):**

| Placeholder | When |
|-------------|------|
| No conversations yet | Inbox list empty (no `wa_conversations`) |
| Select a conversation | Middle column, desktop, nothing selected |
| No property linked | Context panel: contact exists but no property/tenancy match |
| No open maintenance | Context panel: no active MR for contact |
| No messages yet | Thread empty for selected conversation |

### Phase B0 — acceptance criteria

- [ ] Inbox uses three columns on desktop (≥992px); layout does not break existing reply / send-by-event / status forms.
- [ ] Conversation list shows name + preview + time + status — not raw `#id · phone` only.
- [ ] Messages render as left/right bubbles with timestamps and OUT delivery indicator.
- [ ] Composer area sticky at bottom; graphite Send button; gold accents on active state / key actions.
- [ ] Mobile: user can open a chat and return to list without losing `conversation_id` in URL.
- [ ] Empty states show in list, thread, and context (no blank white panels).
- [ ] Initial load shows skeleton cards/panel, then content (no full-page spinner only).

---

## Phase B — Daily operations tool

**Goal:** Inbox is usable for support: who is this, what property/RA/MR, fast replies, automatic events on plugin actions.

| # | Item | Description |
|---|------|-------------|
| B1 | **Context panel (data)** | Populate right column: resolve customer from `wa_contacts` + Sukoon DB (property, RA, MR, agent). Quick links: View Property, View Agreement, View Maintenance. |
| B2 | **Canned replies** | Admin-defined snippets (en/hi): insert into reply or send as text inside 24h window. |
| B3 | **Internal notes** | Agent notes on conversation; not sent to WhatsApp; visible to team only. |
| B4 | **Conversation tags** | Simple fixed tags for **filtering only** (no CRM): Hot Lead, Tenant, Owner, Vendor, Renewal Due, Payment Pending, Verification, Maintenance. Manual + optional auto-tag from `customer_type` / keyword / event. |
| B5 | **Agent assignment** | UI to set `assigned_agent_id`; filter “my conversations”; optional notify on new inbound when assigned. |
| B6 | **`maintenance_assigned`** | Wire `Whatsapp::notify` when MR assigned (tenant/owner “we’re on it”) — catalog currently manual-only. |
| B7 | **`agreement_renewal`** | Wire from Renewal plugin schedule (align with email/push); enable WhatsApp toggle in renewal settings. |
| B8 | **`rent_received`** | Wire after successful rent payment (Cashfree/webhook) — confirmation to tenant. |
| B9 | **Media attachments** *(late B, optional)* | **Promote from Phase C when inbound repair photos are common.** Inbound images in thread; attach/view on MR; outbound in 24h where Meta allows. |

### Phase B — suggested order

0. **Inbox experience (B0)** — layout, cards, bubbles, filters shell, mobile  
1. Context panel data (B1) — plug Sukoon entities into right column  
2. Canned replies + quick action bar (B2)  
3. Internal notes (B3)  
4. Conversation tags (B4) — powers filter bar labels (Maintenance, Rent, etc.)  
5. Wire events (B6, B7, B8) — can parallelize with backend dev  
6. Agent assignment (B5) — “Assigned” / “Unassigned” filters need this  
7. Media attachments (B9) — **if** tenants already send leakage/damage photos on WhatsApp; otherwise keep in Phase C  

**Media decision rule:** If maintenance photos are already common inbound → do **B9 in late Phase B**. If rare today → leave media in **C3 (Phase C)**; keywords/automation are lower priority than proof photos for MR.

### Phase B — acceptance criteria

- [ ] Opening conversation #N shows linked customer + at least one of: property, RA ref, MR ref (when data exists).
- [ ] Agent can send a canned reply in &lt; 3 clicks inside open window.
- [ ] Agent can add a note; note does not appear as OUT message to customer.
- [ ] Staff can tag a conversation and filter inbox list by at least one tag (e.g. Maintenance, Tenant).
- [ ] `maintenance_assigned` fires on assign action when event enabled + template approved.
- [ ] `agreement_renewal` fires from renewal job/admin when WhatsApp enabled.
- [ ] `rent_received` fires once per successful payment with correct template vars.

---

## Phase C — Scale and convenience

**Goal:** Batch utility reminders and light automation — still opt-in / existing customers only.

| # | Item | Description |
|---|------|-------------|
| C1 | **Scheduled rent/renewal reminders** | Batch utility sends (e.g. rent due date, renewal 60/30/15/7) — not cold marketing; log per recipient. |
| C2 | **Off-hours auto reply** | Outside configured hours: one template or text auto-reply + flag conversation; queue for next business day. |
| C3 | **Media attachments** | Inbound images/docs in thread; optional link/download to MR; outbound media in 24h window where Meta allows. |
| C4 | **Keyword automation (basic)** | e.g. `rent`, `repair`, `agreement` → short reply + tag; route to human; no visual flow builder. |

### Phase C — prerequisites

- Phase A complete (production + failure visibility).  
- Phase B context panel (keywords/tags need somewhere to land).  
- **Opt-in / consent log** before C1 batch sends (even for existing tenants).

### Phase C — acceptance criteria

- [ ] Admin can schedule rent reminder batch for owner-tenancy list with preview count and failure report.
- [ ] Message received at 22:00 gets off-hours reply; human reply next morning still works.
- [ ] Tenant photo in WhatsApp appears in inbox; staff can open or attach to MR.
- [ ] Keyword “repair” triggers defined reply and does not break human takeover.

### Phase C — suggested order

1. Off-hours auto reply (C2) — before keywords  
2. Scheduled rent/renewal (C1)  
3. Media attachments (C3) — earlier if repair photos are already common inbound  
4. Keyword automation (C4)  

---

## Explicitly out of scope

- Multi-tenant WhatsApp product for other businesses  
- No-code / visual chatbot builder  
- AI knowledge-base chatbot (revisit only after C2–C4 stable for months)  
- Instagram / Messenger unified inbox  
- Contact import / CRM for purchased lead lists  
- Zapier, HubSpot, Shopify catalog, Click-to-WhatsApp ads manager  
- Multiple WABA numbers (unless business adds second brand later)  
- Custom “campaign” UI unrelated to Sukoon entities  

---

## Event map (target state)

| Event key | Phase | Trigger (Sukoon) |
|-----------|-------|------------------|
| `agreement_ready` | Baseline | RA generated / signed copy ready |
| `vendor_assigned` | Baseline | Agent assigns vendor on MR |
| `maintenance_resolved` | Baseline | MR marked resolved |
| `rent_reminder` | Baseline | Admin rent reminder (owner tenancies) |
| `owner_payment_request` | Baseline | Owner-responsibility MR resolved + cost |
| `maintenance_assigned` | B6 | MR assigned to agent/vendor |
| `agreement_renewal` | B7 | Renewal reminder schedule |
| `rent_received` | B8 | Rent payment success webhook |
| `verification_completed` | Optional | Trust verification complete |
| `otp` | Optional | Auth OTP via WhatsApp vs SMS |

---

## Permissions and i18n

- Inbox: `has_permissions('inbox', 'whatsapp')` (existing).  
- Events/templates: `has_permissions('events', 'whatsapp')` or `templates` (existing).  
- All new UI strings: `whatsapp::whatsapp.*` in `resources/lang/en/whatsapp.php` and `hi/whatsapp.php`.  

---

## Deploy notes (plugin only)

```bash
# After plugin changes on server (as www user)
sudo -u www php /www/wwwroot/admin-homes/artisan migrate --force --path=app/Plugins/Whatsapp/database/migrations
sudo -u www php /www/wwwroot/admin-homes/artisan optimize:clear
sudo -u www php /www/wwwroot/admin-homes/artisan queue:work --queue=whatsapp --once   # smoke test
```

No WRTeam core edits. Wire triggers in **other plugins** via `Whatsapp::notify()` or `WhatsappPlatformEventService` only.

---

## Document history

| Version | Date | Notes |
|---------|------|-------|
| 1.0 | 2026-06-03 | Final Sukoon-only roadmap: Phases A/B/C, acceptance criteria, out of scope |
| 1.1 | 2026-06-03 | Phase B: conversation tags (B4); media optional late B (B9) vs C3 |
| 1.2 | 2026-06-03 | Phase B0: three-column inbox UI polish before context data (B1) |
| 1.3 | 2026-06-03 | B0.7 empty states, B0.8 skeleton loaders |
| 1.4 | 2026-06-03 | SUKOON-HOMES-RULES section; document identity (final file pointer) |

---

## Phase summary (quick reference)

| Phase | Focus |
|-------|--------|
| **A** | Production Meta, template preview, webhook health, failure dashboard |
| **B0** | Inbox UI: 3-column, cards, bubbles, filters, empty states, skeletons |
| **B** | Context data, canned replies, notes, tags, assignment, wire events, optional media |
| **C** | Scheduled reminders, off-hours, media (if not B9), keywords |

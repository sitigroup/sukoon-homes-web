# Sukoon Homes WhatsApp Phase 1 (Meta Cloud API)

## Module scope
- Plugin path: `app/Plugins/Whatsapp`
- No WRTeam core logic edited directly; only optional provider registration patch script with backup.
- Database queue only (`database`, queue: `whatsapp`)
- Security: Sanctum-protected admin routes, permission middleware, webhook verify token + `X-Hub-Signature-256`

## Created tables
- `wa_settings`
- `wa_templates`
- `wa_contacts`
- `wa_conversations`
- `wa_messages`
- `wa_message_status_log`
- `wa_audit_log`
- `jobs` (if missing)
- `failed_jobs` (if missing)

## Screens
- `/whatsapp/settings` (Meta IDs, token/app secret encrypted at rest, environment switch, test connection, webhook status indicator)
- `/whatsapp/templates` (sync, approved template map, enable/disable)
- `/whatsapp/inbox` (conversation list, search, assignment, status, reply, 24-hour rule enforcement)

## Public webhook
- `GET /api/whatsapp/webhook` → Meta verify challenge
- `POST /api/whatsapp/webhook` → signature verification + async queue processing

## Required commands
```bash
php artisan migrate --force
php artisan optimize:clear
pm2 start "php artisan queue:work database --queue=whatsapp --sleep=3 --tries=3 --timeout=90" --name sukoon-whatsapp-queue
```

## Acceptance checklist
- [ ] Meta webhook GET verification works
- [ ] Incoming message stored in `wa_messages`
- [ ] Conversation visible in `/whatsapp/inbox`
- [ ] Agent reply works within 24h window
- [ ] Reply blocked outside 24h with template fallback
- [ ] Approved template sends successfully
- [ ] Delivery status updates from webhook status events
- [ ] Failed jobs logged in `failed_jobs`
- [ ] PM2 worker `sukoon-whatsapp-queue` running
- [ ] Confirm no WRTeam core business logic edits


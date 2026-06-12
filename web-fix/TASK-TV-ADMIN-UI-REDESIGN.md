# TASK — Trust Verification Admin Panel Premium UI Redesign

**Scope:** Plugin admin views only (`plugins/TrustVerification/views/admin/`).  
**No** database, API, or WRTeam core changes.

## Design system

Shared partial: `views/admin/partials/tv-admin-theme.blade.php`

| Token | Value |
|-------|--------|
| Primary graphite | `#1F2937` |
| Hover | `#111827` |
| Page background | `#F8F9FA` |
| Cards | `#FFFFFF` |
| Border | `#D1D5DB` |
| Muted | `#6B7280` |
| Gold accent | `#B89A4A` (privacy/help only) |
| Radius | 14–16px |

### Reusable classes

`tv-admin-page`, `tv-admin-header`, `tv-admin-card`, `tv-admin-grid`, `tv-admin-btn-primary`, `tv-admin-btn-secondary`, `tv-admin-chip-success|warning|danger|muted`, `tv-admin-filter`, `tv-admin-table`, `tv-admin-section-title`, `tv-admin-accordion`, `tv-admin-empty-state`, `tv-admin-entity-card`, `tv-admin-list-card`, `tv-detail-grid`

Legacy aliases retained: `tv-card`, `tv-btn-primary`, `tv-kpi`, etc.

## Pages updated

1. **Orders list** — 6 KPI cards (total, pending payment, in progress, completed, overdue, completed 7d), filters, `tv-admin-table`
2. **Order detail** — 2-column `tv-detail-grid`, accordions; payment/status on right; audit full-width
3. **Packages** — header, filter, premium table
4. **Cities** — list cards + add-city card
5. **Automation** — grouped cards (documents, provider, order numbers, PII, webhook, reconcile, runs)
6. **Content CMS** — `tv-admin-page` aligned with shared theme
7. **Sample Reports** — premium table + uploaded date
8. **Reference check** — entity cards per contact
9. **Police verification** — premium card + chips

## Function safety

All forms, buttons, routes, and permission gates preserved. Nothing removed or hidden.

## Deploy

```bash
# From dev machine
scp -r plugins/TrustVerification/views srv1534644:/tmp/tv-ui/
scp plugins/TrustVerification/Services/TrustVerificationService.php srv1534644:/tmp/tv-ui/

ssh srv1534644 "
  cp -r /tmp/tv-ui/views/admin/* /www/wwwroot/admin-homes/app/Plugins/TrustVerification/views/admin/
  cp /tmp/tv-ui/TrustVerificationService.php /www/wwwroot/admin-homes/app/Plugins/TrustVerification/Services/
  chown -R www:www /www/wwwroot/admin-homes/app/Plugins/TrustVerification
  find /www/wwwroot/admin-homes/app/Plugins/TrustVerification -type d -exec chmod 755 {} \;
  find /www/wwwroot/admin-homes/app/Plugins/TrustVerification -type f -exec chmod 644 {} \;
  cd /www/wwwroot/admin-homes && sudo -u www php artisan optimize:clear
"
```

**Never run:** `php artisan config:cache`

## Rollback

Restore `views/admin/` and `Services/TrustVerificationService.php` from backup; `chown` + `optimize:clear`.

## QA checklist

- [ ] Orders list KPIs and filters
- [ ] Order detail: save status, checks, report, reference, police PDF
- [ ] Packages create/edit
- [ ] Cities add/edit/toggle
- [ ] Automation save + reconcile dry run
- [ ] Content CMS edit/publish/reorder
- [ ] Sample reports upload/preview/delete

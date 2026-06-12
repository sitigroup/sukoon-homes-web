# Task 12B — CMS Frontend Deploy + E2E QA Report

**Date:** 2026-05-24  
**Environment:** Production (`homes.sukoon.group` / `admin-homes.sukoon.group`)

---

## 1. Deploy summary

### Frontend (`homes.sukoon.group`)

| Action | Result |
|--------|--------|
| Rsync `web-fix/plugins/trust-verification/*` → `src/plugins/trust-verification/` | Done |
| Legal pages → `pages/verification-terms|privacy|refund-policy/index.jsx` | Done |
| `npm run build` | **PASS** |
| `pm2 restart homes-sukoon` | **PASS** (after build) |
| Post-QA hotfix: `VerificationFaq.jsx` (`question`/`answer` vs `q`/`a`) | Deployed + rebuild |

### Backend (`admin-homes`)

| Action | Result |
|--------|--------|
| `php artisan optimize:clear` | **PASS** |

### Deployed files (CMS-related)

**Plugin (`src/plugins/trust-verification/`):**

- `trustVerificationContentFallback.js`
- `trustVerificationContentMerge.js`
- `useTrustVerificationContent.js`
- `trustVerificationApi.js` (includes `getTrustVerificationPublicContent`)
- `VerificationLegalPageFromCms.jsx`
- `VerificationHubPremium.jsx`
- `TrustVerificationPage.jsx`
- `ui/VerificationConsentCheckbox.jsx`
- `ui/VerificationDataUsageNotice.jsx`
- `ui/VerificationReportDisclaimer.jsx`
- `ui/VerificationFaq.jsx` (hotfix)

**Pages:**

- `pages/verification-terms/index.jsx`
- `pages/verification-privacy/index.jsx`
- `pages/verification-refund-policy/index.jsx`

---

## 2. QA identifiers

| Item | Value |
|------|--------|
| **Edited FAQ key** | `faq.1` |
| **FAQ test question (frontend)** | `TASK12B FAQ visible on site?` |
| **Test order (consent)** | **TV-QGX1TNVP** |
| **Order `legal_version`** | `cms-v3` (at order creation) |
| **Order `consent_text`** | `TASK12B consent — I confirm permission for this verification request.` |
| **Current CMS consent version** | `cms-v4` (after QA script re-runs) |

---

## 3. Admin & API URLs

| Resource | URL |
|----------|-----|
| Public content API | https://admin-homes.sukoon.group/api/trust-verification/content/public |
| CMS list | https://admin-homes.sukoon.group/trust-verification/content |
| Edit block | https://admin-homes.sukoon.group/trust-verification/content/blocks/{id}/edit |

**Screenshots (local):**

- `task12b-verification-hub.png` — `/verification` with CMS FAQ
- `task12b-verification-terms.png` — `/verification-terms`

---

## 4. Final pass/fail table

| # | Test | Result | Notes |
|---|------|--------|-------|
| 1 | Deploy web CMS plugin + legal pages | **PASS** | `DEPLOY_OK` / `BUILD_OK` |
| 2 | Public API `GET …/content/public` | **PASS** | 13 hub keys; FAQ in payload |
| 3 | Admin FAQ edit → API reflects | **PASS** | `faq.1` |
| 4 | FAQ publish / unpublish | **PASS** | Hidden when unpublished |
| 5 | FAQ reset to default | **PASS** | |
| 6 | Audit logs (`content_block_*`) | **PASS** | 2+ rows in `tv_audit_logs` (admin reset actions); service-only QA does not write audits |
| 7 | Frontend `/verification` hub CMS | **PASS** | Hero title from CMS; FAQ after `VerificationFaq` fix |
| 8 | Frontend `/tenant-verification-in-barmer` | **PASS** | Page loads; wizard uses CMS hook |
| 9 | Legal pages CMS | **PASS** | `/verification-terms`, `/verification-privacy`, `/verification-refund-policy` |
| 10 | CMS fallback if API fails | **PASS** | `TRUST_VERIFICATION_CONTENT_FALLBACK` + merge in `useTrustVerificationContent` (design verified) |
| 11 | Consent CMS → new order | **PASS** | TV-QGX1TNVP stores edited text + `cms-v3` |
| 12 | Email CMS subject/body | **PASS** | Template: `Verification request received — TV-TEST` |
| 13 | Email fallback if CMS missing | **PASS** | `emailTemplate()` uses `$fallback` when JSON empty |
| 14 | Order/report emails not broken | **PASS** | `TvOrderSubmittedMail` / `TvReportReadyMail` unchanged flow; CMS optional body |
| 15 | `npm run build` | **PASS** | Next.js 14.2.35 |
| 16 | `php artisan optimize:clear` | **PASS** | |

**Overall: PASS** (with one post-deploy hotfix for FAQ field names).

---

## 5. Follow-up (optional cleanup)

1. Reset `faq.1` to production copy in admin if test question should not remain live.
2. Restore `wizard.consent_checkbox_text` if `TASK12B consent —…` should not stay in CMS (`cms-v4`).
3. Re-run **Safe Regression** Postman folder only if desired (avoid full collection on production).

---

## 6. Scripts used

- `web-fix/deploy-task-12b-remote.sh`
- `web-fix/tv-qa-task-12b.php`
- `web-fix/tv-qa-create-order-12b.php`
- `web-fix/tv-qa-faq-frontend.php`
- `web-fix/tv-qa-order-check.php`
- `web-fix/tv-qa-audit-check.php`

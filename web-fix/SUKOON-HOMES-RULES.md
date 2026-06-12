# Sukoon Homes — Critical Production Rules

Authoritative checklist for agents, deploys, and incident response.  
Applies to **homes.sukoon.group** (Next.js) and **admin-homes.sukoon.group** (Laravel + plugins).

> **Agents:** Read this file in full before any Sukoon Homes code change, deploy, or production investigation.

---

## Permanent rule (read first)

**Always read:** `web-fix/SUKOON-HOMES-RULES.md`

### Before any code

1. **Identify:**
   - plugin/module scope
   - affected frontend/backend files
   - DB impact
   - rollback path

2. **Never deploy without:**
   - backup
   - build pass
   - PM2 check
   - curl health check
   - QA verification

3. **Never assume production data.** Verify DB first.

4. **Never use bootstrap/admin override commands as normal business flow.**  
   (`activate-owner-svo`, `--admin-verify` backfill, “Issue Verified Owner Badge” override are ops/QA only.)

5. **Public trust:** Owner only.  
   **Tenant trust:** Private only.

6. **If changing API:** Keep backward compatibility.

7. **If touching production:** Prefer additive change over destructive change.

8. **Stability > feature speed.**

---

## 1. Code boundaries

1. **Never modify WRTeam core files** unless explicitly approved by the project owner.
2. All new systems must be:
   - **update-safe**
   - **plugin/module based**
   - **reversible**
   - **production-safe**
3. **Always backup** before production changes (plugin dir, DB snapshot, or documented restore point).
4. **Preserve production stability** over feature speed.
5. **Avoid breaking** existing APIs or frontend behavior unless changes are versioned safely.

---

## 2. Privacy & public trust display

4. **Never expose** on public or unauthenticated surfaces:
   - tenant private data
   - raw trust score (beyond what the public-trust API is designed to return)
   - religion / caste
   - income
   - admin notes
   - uploaded documents
   - police / reference internals

5. **Tenant trust is PRIVATE.**  
   Do **not** show **Trusted Tenant** on property cards, search, map, or listing grids.

6. **Only Trusted Owner** may appear publicly (via issued SVO + `owner_trust_badge`).

7. **Never hardcode fake verification states.** Badges must come from real issued rows (`tv_verification_badges`) and eligible orders.

---

## 3. Infrastructure — hands off by default

13. **Never change** aaPanel, Cloudflare, firewall, or port settings during feature deployment unless **diagnosing infrastructure issues** with explicit approval.

14. **If Cloudflare shows 522:**
    - First test origin locally on the server:
      ```bash
      curl -I -H "Host: admin-homes.sukoon.group" http://127.0.0.1/api/web-settings
      ```
    - If origin returns **200**, the issue is **Cloudflare proxy/DNS**, not Laravel.

15. **Before blaming app code**, always verify:
    - `pm2 status`
    - `systemctl status httpd`
    - `systemctl status php-fpm-83` (or active PHP-FPM unit)
    - `curl` origin directly (with correct `Host` header)
    - disk space (`df -h`)
    - memory (`free -h`)

16. **Never restart PM2 blindly** during infra incidents. Check build status and logs first (`pm2 logs homes-sukoon`, build output, Laravel `storage/logs`).

---

## 4. Production deploy sequence

17. **Order of operations:**

    ```
    Backup → Deploy → Build → PM2 → curl checks → QA → close task
    ```

    Details:

    | Step | Action |
    |------|--------|
    | **Backup** | Plugin folder / DB as appropriate |
    | **Deploy** | Rsync plugin to `admin-homes/app/Plugins/…`; sync `homes/src/plugins/…` for frontend |
    | **Build** | `cd /www/wwwroot/homes.sukoon.group && npm run build` — **must pass** before PM2 |
    | **PM2** | `pm2 restart homes-sukoon` only after successful build |
    | **curl checks** | See below |
    | **QA** | API + UI per task spec |
    | **Close** | Document root cause, files, migrations, QA, rollback |

7. **Frontend:** build must pass before PM2 restart.

8. **After every deploy**, run:

    ```bash
    curl -I https://homes.sukoon.group
    curl -I https://admin-homes.sukoon.group/api/web-settings
    pm2 status
    ```

    On the server, also validate origin when debugging 522:

    ```bash
    curl -I -H "Host: admin-homes.sukoon.group" http://127.0.0.1/api/web-settings
    ```

---

## 5. Deliverables (every production change)

10. Always provide:

    - **root cause**
    - **changed files**
    - **migration names** (if any)
    - **QA result**
    - **rollback note**

---

## 6. Trust verification specifics

| Surface | Owner (SVO) | Tenant (SVT) |
|---------|-------------|----------------|
| Property cards / search / map | ✅ `owner_trust_badge` only | ❌ Never |
| Public API `public-trust` | `owner_trust_badge`, `sukoon_verified_owner` | `tenant_trust_badge` for profile logic only; not on cards |
| My Profile / My Orders | Per product spec | ✅ Private |
| Admin | Full tooling | Full tooling |

**Issued badges table:** `tv_verification_badges` (SVO/SVT numbers).  
**Catalog badges table:** `tv_trust_badges` (score slugs — do not use for listing pills).

**Useful commands:**

```bash
php artisan trust-verification:issue-missing-badges --dry-run
php artisan trust-verification:issue-missing-badges --owners-only --admin-verify
php artisan trust-verification:activate-owner-svo {customer_id} --from-order={id}  # ops/bootstrap only
```

**Never run on this server:**

```bash
php artisan config:cache   # breaks constants.php
```

---

## 7. Deploy paths (reference)

| Component | Path |
|-----------|------|
| Laravel admin | `/www/wwwroot/admin-homes` |
| TrustVerification plugin | `/www/wwwroot/admin-homes/app/Plugins/TrustVerification` |
| Next.js homes | `/www/wwwroot/homes.sukoon.group` |
| TV frontend plugin | `/www/wwwroot/homes.sukoon.group/src/plugins/trust-verification` |
| Safe plugin deploy script | `web-fix/deploy-plugin-safe.sh` |

---

## 8. Rollback (generic)

1. Restore plugin directory from backup (or prior rsync snapshot).
2. `cd /www/wwwroot/admin-homes && sudo -u www php artisan optimize:clear`
3. Rebuild frontend if UI changed: `npm run build` → `pm2 restart homes-sukoon`
4. Re-run curl checks (§4).
5. For data rollback: revert specific DB rows (e.g. revoke badge) — document in task notes.

---

## 9. Developer workstation backup (before PC format / reinstall)

**Reminder:** Cursor chat history is **local**. Formatting the PC without a backup **will delete sidebar chats** unless you copy the folders below.

### Before any format or reinstall

1. **Fully close Cursor** (Task Manager — no `Cursor.exe`).
2. Run on Windows (script on Desktop):
   ```powershell
   cd $env:USERPROFILE\Desktop
   Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
   .\backup-cursor.ps1
   ```
   Or save to USB/cloud:
   ```powershell
   .\backup-cursor.ps1 -Destination "D:\MyBackup\cursor-backup"
   ```
3. Copy the output folder to **USB + cloud** (two copies).
4. Also back up **separately** (not inside Cursor):
   - Project repo: `cursr` (git push or zip)
   - SSH: `%USERPROFILE%\.ssh`
   - Sukoon notes/scripts: `web-fix`, deploy scripts on Desktop

### Must-copy folders (manual backup)

| Folder | Purpose |
|--------|---------|
| `%APPDATA%\Cursor\User` | Sidebar chats (`globalStorage\state.vscdb`) |
| `%USERPROFILE%\.cursor` | Agent transcripts, project metadata, MCP configs |

Confirm backup contains **`state.vscdb`** (non-zero size).

### After reinstall

1. Install Cursor; sign in with the **same account**.
2. Restore:
   ```powershell
   .\restore-cursor-chats.ps1 -BackupRoot "D:\MyBackup\cursor-backup\AppData-Roaming-Cursor"
   ```
3. Copy backed-up **`.cursor`** → `%USERPROFILE%\.cursor`.
4. Re-open project folder; use git clone/pull for code.

**Scripts:** `Desktop\backup-cursor.ps1` (backup) · `Desktop\restore-cursor-chats.ps1` (restore)

---

*Last updated: TASK 16E + infra rules 13–17 + Cursor backup reminder.*

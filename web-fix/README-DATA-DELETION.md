# Data deletion page (Meta App Review)

**Public URL:** `https://homes.sukoon.group/data-deletion`

## Files

| Staged (web-fix) | Deploy target |
|------------------|---------------|
| `pages-data-deletion.jsx` | `pages/data-deletion/index.jsx` |
| `components/legal/DataDeletionInstructions.jsx` | `src/components/legal/DataDeletionInstructions.jsx` |
| `components/legal/dataDeletionContent.js` | `src/components/legal/dataDeletionContent.js` |

## Deploy (server)

```bash
cd /path/to/cursr/web-fix
bash deploy-data-deletion-page.sh
```

Requires `HOMES_ROOT` if not `/www/wwwroot/homes.sukoon.group`.

## Meta App Review

Use this URL in the app dashboard: **User data deletion** / callback URL field:

`https://homes.sukoon.group/data-deletion`

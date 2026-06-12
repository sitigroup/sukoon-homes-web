# Sukoon Homes — Performance Safe Mode

Reduces Cursor CPU/RAM use and avoids OOM by shrinking what gets indexed and what the agent scans.

## What was added

| File | Purpose |
|------|---------|
| `.cursorignore` | Hard block — Agent, search, Tab, @-mentions skip these paths |
| `.cursorindexingignore` | Codebase index skips these paths |
| `.vscode/settings.json` | File watcher + search excludes (less disk/CPU churn) |
| `.cursor/rules/performance-safe-mode.mdc` | Agent: narrow scope, no full-repo scans |

## Excluded folders

- `node_modules/`
- `.next/`
- `vendor/`
- `storage/logs/`
- `bootstrap/cache/`
- `public/build/`

Plus workspace extras: `.dart_tool/`, `backups/`, common build/cache dirs.

## Apply after setup

1. **Reload Cursor** — Command Palette → `Developer: Reload Window`
2. **Reindex** — Command Palette → `Cursor: Resync Index` (or Codebase → Re-index)
3. **New chat** — Start a **new** Agent/Composer chat. Do **not** resume an old thread (avoids restoring huge prior context).
4. **Optional UI** — Cursor Settings → Features → Codebase Indexing: confirm indexed file count dropped.

## Composer / context

- Do not resume long crashed sessions.
- Pin only the 1–3 files you are editing (`@filename`).
- Keep one workspace folder open (`cursr`) if possible; avoid multi-root with duplicate trees.

## If OOM continues

- Close unused editor tabs.
- Disable unused extensions for this workspace.
- Open only the subfolder you need (e.g. `web-fix/`) as workspace root instead of the whole monorepo.

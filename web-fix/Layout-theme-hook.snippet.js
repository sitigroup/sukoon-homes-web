/**
 * Reference snippet — add to Layout.jsx after dispatch(setWebSettings({ data }))
 * Deploy via: node web-fix/patch-theme-layout.js
 */
import { mergeThemeWithWebSettings } from "@/plugins/theme";

// Inside fetchWebSettings(), after setWebSettings:
mergeThemeWithWebSettings(null, data);

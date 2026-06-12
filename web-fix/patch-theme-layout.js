/**
 * Extend Layout.jsx to re-apply Sukoon theme after web settings load (minimal hook).
 * Run: node web-fix/patch-theme-layout.js
 */
const fs = require("fs");
const path = require("path");

const homesRoot = process.env.HOMES_ROOT || "/www/wwwroot/homes.sukoon.group";
const layoutPath = path.join(homesRoot, "src/components/layout/Layout.jsx");

const altPath = path.join(homesRoot, "components/layout/Layout.jsx");
const target = fs.existsSync(layoutPath) ? layoutPath : altPath;

if (!fs.existsSync(target)) {
  console.error("Layout.jsx not found:", layoutPath);
  process.exit(1);
}

let src = fs.readFileSync(target, "utf8");

if (src.includes("mergeThemeWithWebSettings")) {
  console.log("Layout.jsx: theme hook already present.");
  process.exit(0);
}

src = src.replace(
  /(import\s+\{\s*setWebSettings\s*\}\s+from\s+["']@\/redux\/slices\/webSettingSlice["'];?\n)/,
  `$1import { mergeThemeWithWebSettings } from "@/plugins/theme";\n`
);

const anchor = "dispatch(setWebSettings({ data }));";
if (!src.includes(anchor)) {
  console.error("Could not find setWebSettings anchor in Layout.jsx");
  process.exit(1);
}

src = src.replace(
  anchor,
  `${anchor}
      mergeThemeWithWebSettings(null, data);`
);

fs.writeFileSync(target, src);
console.log("Patched Layout.jsx — theme merges after web settings fetch.");

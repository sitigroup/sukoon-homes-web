/**
 * Wrap Next.js _app with SukoonThemeProvider (minimal core hook).
 * Run: node web-fix/patch-theme-app.js
 */
const fs = require("fs");
const path = require("path");

const homesRoot = process.env.HOMES_ROOT || "/www/wwwroot/homes.sukoon.group";
const appPath = path.join(homesRoot, "pages/_app.js");

if (!fs.existsSync(appPath)) {
  console.error("_app.js not found:", appPath);
  process.exit(1);
}

let src = fs.readFileSync(appPath, "utf8");

if (src.includes("SukoonThemeProvider")) {
  console.log("_app.js: SukoonThemeProvider already wired.");
  process.exit(0);
}

if (!src.includes('from "react-redux"') && !src.includes("from 'react-redux'")) {
  console.error("Could not find react-redux import anchor.");
  process.exit(1);
}

src = src.replace(
  /(import\s+\{\s*Provider\s*\}\s+from\s+["']react-redux["'];?\n)/,
  `$1import { SukoonThemeProvider } from "@/plugins/theme";\n`
);

const wrapNeedle = "<Provider store={store}>";
const wrapReplace = `<Provider store={store}>
            <SukoonThemeProvider>`;

if (!src.includes(wrapNeedle)) {
  console.error("Could not find <Provider store={store}> anchor.");
  process.exit(1);
}

src = src.replace(wrapNeedle, wrapReplace);
src = src.replace(
  "</Provider>",
  "            </SukoonThemeProvider>\n          </Provider>"
);

fs.writeFileSync(appPath, src);
console.log("Patched _app.js — SukoonThemeProvider wraps app.");

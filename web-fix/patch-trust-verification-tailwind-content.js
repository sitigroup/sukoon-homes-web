/**
 * Add Trust Verification plugin to Tailwind content paths (fixes purged classes).
 * Run from repo: node web-fix/patch-trust-verification-tailwind-content.js
 */
const fs = require("fs");
const path = require("path");

const homesRoot = process.env.HOMES_ROOT || "/www/wwwroot/homes.sukoon.group";
const configPath = path.join(homesRoot, "tailwind.config.js");

if (!fs.existsSync(configPath)) {
  console.error("tailwind.config.js not found:", configPath);
  process.exit(1);
}

let src = fs.readFileSync(configPath, "utf8");
const needle = '"./src/plugins/**/*.{js,ts,jsx,tsx,mdx}"';

if (src.includes(needle)) {
  console.log("Tailwind already includes src/plugins — no change.");
  process.exit(0);
}

if (!src.includes("content: [")) {
  console.error("Could not find content: [ in tailwind.config.js");
  process.exit(1);
}

src = src.replace(
  "content: [",
  `content: [\n    ${needle},`
);

fs.writeFileSync(configPath, src);
console.log("Patched tailwind.config.js — added src/plugins to content paths.");

#!/usr/bin/env node
/**
 * Add Next.js rewrites so SEO URLs work:
 *   /tenant-verification-in-jodhpur → /tenant-verification-in/jodhpur
 *   /owner-verification-in-jodhpur  → /owner-verification-in/jodhpur
 *
 * Run from homes root:
 *   node /path/to/cursr/web-fix/patch-trust-verification-next-rewrites.js
 */
const fs = require("fs");
const path = require("path");

const homesRoot = process.cwd();
const configPath = path.join(homesRoot, "next.config.js");

if (!fs.existsSync(configPath)) {
  console.error("ERROR: next.config.js not found in", homesRoot);
  process.exit(1);
}

const marker = "trustVerificationCityRewrites";
let content = fs.readFileSync(configPath, "utf8");

if (content.includes(marker)) {
  console.log("Already patched:", marker);
  process.exit(0);
}

const block = `
const ${marker} = [
  { source: "/tenant-verification-in-:citySlug", destination: "/tenant-verification-in/:citySlug" },
  { source: "/owner-verification-in-:citySlug", destination: "/owner-verification-in/:citySlug" },
];
`;

if (!content.includes("async rewrites()")) {
  console.error("ERROR: Could not find async rewrites() in next.config.js");
  process.exit(1);
}

content = block + content;
content = content.replace(
  /return \[\.\.\.nearbyPlacesProxyRewrites\(\)\];/,
  `return [...${marker}, ...nearbyPlacesProxyRewrites()];`
);

fs.writeFileSync(configPath, content);
console.log("Patched next.config.js with", marker);

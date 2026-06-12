#!/usr/bin/env node
/**
 * Patches root next.config.js to register Nearby Places proxy rewrites.
 * Safe to run multiple times (no-op if already patched).
 */

const fs = require("fs");
const path = require("path");

const projectRoot = path.resolve(__dirname, "..", "..", "..", "..");
const cfgPath = path.join(projectRoot, "next.config.js");

const requireLine =
  'const { nearbyPlacesProxyRewrites } = require("./src/plugins/nearby-places/nextRewrites.cjs");\n';

const rewritesBlock = `  async rewrites() {
    return [...nearbyPlacesProxyRewrites()];
  },
`;

function main() {
  if (!fs.existsSync(cfgPath)) {
    console.error("next.config.js not found at", cfgPath);
    process.exit(1);
  }

  let src = fs.readFileSync(cfgPath, "utf8");

  if (src.includes("nearbyPlacesProxyRewrites")) {
    console.log("next.config.js already includes nearbyPlacesProxyRewrites — skipping.");
    return;
  }

  const anchorRequire = 'const fs = require("fs");\n';
  if (!src.includes(anchorRequire)) {
    console.error('Expected "', anchorRequire.trim(), '" in next.config.js — aborting.');
    process.exit(1);
  }

  src = src.replace(anchorRequire, anchorRequire + "\n" + requireLine);

  const anchorRewrites =
    /(\s*devIndicators:\s*\{[^}]*buildActivity:\s*false,\s*\},)(\s*\};)/m;

  if (!anchorRewrites.test(src)) {
    console.error("Could not find devIndicators block to insert rewrites — edit next.config.js manually.");
    process.exit(1);
  }

  src = src.replace(anchorRewrites, `$1\n${rewritesBlock}$2`);

  fs.writeFileSync(cfgPath, src, "utf8");
  console.log("Patched", cfgPath);
}

main();

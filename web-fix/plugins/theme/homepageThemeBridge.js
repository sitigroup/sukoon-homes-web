/**
 * Re-applies legacy WRTeam CSS variables on homepage so Layout web-settings
 * cannot leave --primary-color as a low-contrast value. Plugin-only.
 */
import { deriveSemantic } from "./contrastUtils";
import { normalizeThemePayload } from "./themeTokens";
import { DEFAULT_THEME } from "./themePresets";

export function isHomepagePath(pathname = "") {
  const p = String(pathname || "").replace(/\/$/, "") || "/";
  return p === "" || p === "/" || p === "/index";
}

export function syncHomepagePageMarker(pathname) {
  if (typeof document === "undefined") return false;
  const isHome = isHomepagePath(pathname);
  const value = isHome ? "home" : "";
  document.documentElement.dataset.sukoonPage = value;
  document.body.dataset.sukoonPage = value;
  return isHome;
}

export function applyHomepageLegacyVarBridge(themePayload) {
  if (typeof document === "undefined") return;
  if (document.documentElement.dataset.sukoonThemeActive !== "true") return;
  if (document.documentElement.dataset.sukoonPage !== "home") return;

  const normalized = normalizeThemePayload(themePayload || DEFAULT_THEME);
  const semantic = deriveSemantic(normalized.tokens);
  const root = document.documentElement;

  root.style.setProperty("--primary-color", normalized.tokens.primary || "#111827");
  root.style.setProperty("--brand-color", semantic.text_primary);
  root.style.setProperty("--lead-color", semantic.text_secondary);
  root.style.setProperty("--primary-text-color02", semantic.text_secondary);
  root.style.setProperty("--primary-text-color03", semantic.text_primary);
  root.style.setProperty("--border-color", "#E5E7EB");
  root.style.setProperty("--new-border-color", "#E5E7EB");
  root.style.setProperty("--primary-background", normalized.tokens.section || "#F9FAFB");
  root.style.setProperty("--primary-rent", semantic.text_primary);
  root.style.setProperty("--primary-rent-bg", normalized.tokens.section || "#F3F4F6");
  root.style.setProperty("--primary-sell", semantic.text_primary);
  root.style.setProperty("--primary-sell-bg", normalized.tokens.section || "#F3F4F6");
}

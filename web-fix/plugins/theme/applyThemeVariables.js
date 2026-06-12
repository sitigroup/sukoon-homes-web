import { cssVariablesFromTheme, normalizeThemePayload } from "./themeTokens";
import { DEFAULT_THEME } from "./themePresets";
import { buildContrastProtectionCss } from "./buildContrastProtectionCss";
import { buildHomepageContrastCss } from "./buildHomepageContrastCss";
import { applyHomepageLegacyVarBridge } from "./homepageThemeBridge";

const STYLE_ID = "sukoon-theme-vars";

export function applyThemeVariables(themePayload, { mergeWebSettings = null, active = true } = {}) {
  if (typeof document === "undefined") return normalizeThemePayload(themePayload || DEFAULT_THEME);

  const normalized = normalizeThemePayload(themePayload || DEFAULT_THEME);
  const root = document.documentElement;

  const vars = cssVariablesFromTheme(normalized);

  if (mergeWebSettings?.system_color && !themePayload?.tokens?.primary) {
    vars["--primary-color"] = mergeWebSettings.system_color;
  }

  Object.entries(vars).forEach(([name, value]) => {
    root.style.setProperty(name, value);
  });

  root.style.setProperty("font-family", normalized.typography);
  root.dataset.sukoonTheme = normalized.preset_slug || "custom";
  root.dataset.sukoonThemeVersion = String(normalized.version || 1);
  root.dataset.sukoonThemeActive = active ? "true" : "false";

  let styleEl = document.getElementById(STYLE_ID);
  if (!styleEl) {
    styleEl = document.createElement("style");
    styleEl.id = STYLE_ID;
    document.head.appendChild(styleEl);
  }

  styleEl.textContent = active
    ? `${buildBaseEnhancementsCss()}\n${buildContrastProtectionCss()}\n${buildHomepageContrastCss()}`
    : "";

  if (active) {
    applyHomepageLegacyVarBridge(normalized);
  }

  return normalized;
}

function buildBaseEnhancementsCss() {
  return `
    html[data-sukoon-theme-active="true"] body {
      font-family: var(--sukoon-font-family, Manrope, system-ui, sans-serif);
      font-size: 15px;
      -webkit-font-smoothing: antialiased;
      color: var(--sukoon-text-primary, #111827);
      background-color: var(--sukoon-section, #F9FAFB);
    }

    html[data-sukoon-theme-active="true"] .card,
    html[data-sukoon-theme-active="true"] [class*="Card"],
    html[data-sukoon-theme-active="true"] .property-card,
    html[data-sukoon-theme-active="true"] .project-card {
      border-radius: var(--sukoon-radius, 16px) !important;
      box-shadow: var(--sukoon-shadow, 0 4px 20px rgba(0,0,0,0.08));
    }

    html[data-sukoon-theme-active="true"] input,
    html[data-sukoon-theme-active="true"] select,
    html[data-sukoon-theme-active="true"] textarea {
      border-radius: calc(var(--sukoon-radius, 16px) - 4px);
      font-size: 0.9375rem;
    }
  `;
}

export function clearThemeStyles() {
  if (typeof document === "undefined") return;

  const root = document.documentElement;
  const body = document.body;

  root.dataset.sukoonThemeActive = "false";
  delete root.dataset.sukoonTheme;
  delete root.dataset.sukoonThemeVersion;
  delete root.dataset.sukoonPage;

  if (body) {
    delete body.dataset.sukoonPage;
  }

  /* Remove inline --sukoon-* vars; keep --primary-color from WRTeam web settings */
  for (let i = root.style.length - 1; i >= 0; i -= 1) {
    const name = root.style[i];
    if (name && name.startsWith("--sukoon-")) {
      root.style.removeProperty(name);
    }
  }

  root.style.removeProperty("font-family");

  const styleEl = document.getElementById(STYLE_ID);
  if (styleEl) styleEl.textContent = "";

  const auditEl = document.getElementById("sukoon-theme-audit-css");
  if (auditEl) auditEl.textContent = "";
}

export function mergeThemeWithWebSettings(theme, webSettings) {
  if (!webSettings || typeof document === "undefined") return theme;
  /* Layout hook: sync system color only; full theme CSS runs only when API published:true */
  if (webSettings.system_color) {
    document.documentElement.style.setProperty(
      "--primary-color",
      webSettings.system_color
    );
  }
  return theme;
}

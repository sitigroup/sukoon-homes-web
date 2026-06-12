import { DEFAULT_THEME } from "./themePresets";
import { semanticCssVariables } from "./contrastUtils";

const SHADOWS = {
  soft: "0 2px 12px rgba(15, 23, 42, 0.05)",
  medium: "0 4px 20px rgba(15, 23, 42, 0.08)",
  strong: "0 8px 32px rgba(15, 23, 42, 0.14)",
};

export function shadowForIntensity(intensity) {
  return SHADOWS[intensity] || SHADOWS.medium;
}

export function normalizeThemePayload(payload) {
  const base = DEFAULT_THEME;
  const tokens = { ...base.tokens, ...(payload?.tokens || {}) };
  const typography = payload?.typography || base.typography;
  const border_radius = payload?.border_radius || base.border_radius;
  const shadow_intensity = payload?.shadow_intensity || base.shadow_intensity;

  return {
    tokens,
    typography,
    border_radius,
    shadow_intensity,
    shadow: payload?.shadow || shadowForIntensity(shadow_intensity),
    preset_slug: payload?.preset_slug || base.slug,
    version: payload?.version || 1,
  };
}

export function cssVariablesFromTheme(theme) {
  const normalized = normalizeThemePayload(theme);
  const vars = {};

  Object.entries(normalized.tokens).forEach(([key, value]) => {
    vars[`--sukoon-${key.replace(/_/g, "-")}`] = value;
  });

  Object.assign(vars, semanticCssVariables(normalized.tokens));

  vars["--sukoon-font-family"] = normalized.typography;
  vars["--sukoon-radius"] = normalized.border_radius;
  vars["--sukoon-shadow"] = normalized.shadow;
  vars["--primary-color"] = normalized.tokens.primary;

  return vars;
}

/** Tailwind-friendly class bundles — semantic contrast tokens only */
export const sukoonThemeClasses = {
  card:
    "rounded-[var(--sukoon-radius,16px)] border border-[var(--sukoon-border,#E5E7EB)] bg-[var(--sukoon-bg-primary,#FFFFFF)] text-[var(--sukoon-text-primary,#111827)] shadow-[var(--sukoon-shadow,0_4px_20px_rgba(0,0,0,0.08))]",
  btnPrimary:
    "inline-flex items-center justify-center rounded-[var(--sukoon-radius,16px)] bg-[var(--sukoon-button,#111827)] px-5 py-3 text-sm font-semibold text-[var(--sukoon-button-text,#FFFFFF)] transition hover:bg-[var(--sukoon-hover,#000000)] hover:text-[var(--sukoon-button-hover-text,#FFFFFF)]",
  btnSecondary:
    "inline-flex items-center justify-center rounded-[var(--sukoon-radius,16px)] border border-[var(--sukoon-border,#E5E7EB)] bg-[var(--sukoon-bg-primary,#FFFFFF)] px-5 py-3 text-sm font-medium text-[var(--sukoon-text-primary,#111827)] transition hover:bg-[var(--sukoon-section,#F9FAFB)]",
  link: "font-semibold text-[var(--sukoon-link-on-light,#111827)] hover:opacity-90",
  linkOnDark: "font-semibold text-[var(--sukoon-link-on-dark,#FFFFFF)] hover:opacity-90",
  muted: "text-[var(--sukoon-text-secondary,#6B7280)] text-sm",
  section: "bg-[var(--sukoon-section,#F9FAFB)] text-[var(--sukoon-text-primary,#111827)]",
  surfaceDark: "sukoon-surface-dark bg-[var(--sukoon-bg-dark,#111827)] text-[var(--sukoon-text-on-dark,#FFFFFF)]",
};

/** Sukoon Theme — WCAG contrast helpers (plugin-only). */

const MIN_CONTRAST = 4.5;

export const SEMANTIC_DEFAULTS = {
  text_primary: "#111827",
  text_secondary: "#6B7280",
  text_on_dark: "#FFFFFF",
  placeholder: "#9CA3AF",
  bg_primary: "#FFFFFF",
  bg_dark: "#111827",
  border: "#E5E7EB",
};

function normalizeHex(value) {
  const v = String(value || "").trim();
  if (/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(v)) {
    return v.toUpperCase();
  }
  return "#111827";
}

function hexToRgb(hex) {
  let h = normalizeHex(hex).slice(1);
  if (h.length === 3) {
    h = h
      .split("")
      .map((c) => c + c)
      .join("");
  }
  return [
    parseInt(h.slice(0, 2), 16),
    parseInt(h.slice(2, 4), 16),
    parseInt(h.slice(4, 6), 16),
  ];
}

function linearize(channel) {
  return channel <= 0.03928 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4;
}

export function relativeLuminance(hex) {
  const [r, g, b] = hexToRgb(hex);
  const rs = linearize(r / 255);
  const gs = linearize(g / 255);
  const bs = linearize(b / 255);
  return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs;
}

export function contrastRatio(hex1, hex2) {
  const l1 = relativeLuminance(hex1);
  const l2 = relativeLuminance(hex2);
  const lighter = Math.max(l1, l2);
  const darker = Math.min(l1, l2);
  return (lighter + 0.05) / (darker + 0.05);
}

export function isDarkBackground(hex) {
  return relativeLuminance(hex) < 0.45;
}

export function ensureContrast(foreground, background, lightFallback, darkFallback) {
  const fg = normalizeHex(foreground);
  const bg = normalizeHex(background);

  if (fg === bg || contrastRatio(fg, bg) < MIN_CONTRAST) {
    return relativeLuminance(bg) > 0.45 ? lightFallback : darkFallback;
  }
  return fg;
}

export function deriveSemantic(tokens = {}) {
  const textPrimary = tokens.primary ?? SEMANTIC_DEFAULTS.text_primary;
  const textSecondary = tokens.muted ?? SEMANTIC_DEFAULTS.text_secondary;
  const textOnDark = tokens.white ?? SEMANTIC_DEFAULTS.text_on_dark;
  const bgPrimary = tokens.card ?? SEMANTIC_DEFAULTS.bg_primary;
  const bgDark = tokens.sidebar ?? tokens.primary ?? SEMANTIC_DEFAULTS.bg_dark;
  const border = tokens.border ?? SEMANTIC_DEFAULTS.border;
  const link = tokens.link ?? textPrimary;
  const buttonBg = tokens.button ?? bgDark;
  const hoverBg = tokens.hover ?? buttonBg;

  const placeholder = tokens.placeholder ?? SEMANTIC_DEFAULTS.placeholder;

  return {
    text_primary: ensureContrast(textPrimary, bgPrimary, "#111827", "#FFFFFF"),
    text_secondary: ensureContrast(textSecondary, bgPrimary, "#6B7280", "#D1D5DB"),
    text_on_dark: ensureContrast(textOnDark, bgDark, "#FFFFFF", "#111827"),
    placeholder: ensureContrast(placeholder, bgPrimary, "#9CA3AF", "#D1D5DB"),
    bg_primary: bgPrimary,
    bg_dark: bgDark,
    border,
    link_on_light: ensureContrast(link, bgPrimary, "#111827", "#000000"),
    link_on_dark: ensureContrast(link, bgDark, "#FFFFFF", "#F9FAFB"),
    button_text: ensureContrast(textOnDark, buttonBg, "#FFFFFF", "#111827"),
    button_hover_text: ensureContrast(textOnDark, hoverBg, "#FFFFFF", "#111827"),
  };
}

export function semanticCssVariables(tokens = {}) {
  const semantic = deriveSemantic(tokens);
  const vars = {};

  Object.entries(semantic).forEach(([key, value]) => {
    vars[`--sukoon-${key.replace(/_/g, "-")}`] = value;
  });

  vars["--sukoon-text-primary"] = semantic.text_primary;
  vars["--sukoon-text-secondary"] = semantic.text_secondary;
  vars["--sukoon-text-on-dark"] = semantic.text_on_dark;
  vars["--sukoon-bg-primary"] = semantic.bg_primary;
  vars["--sukoon-bg-dark"] = semantic.bg_dark;
  vars["--sukoon-border"] = semantic.border;
  vars["--sukoon-link"] = semantic.link_on_light;
  vars["--sukoon-placeholder"] = semantic.placeholder;

  return vars;
}

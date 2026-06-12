/** Sukoon Theme — preset definitions (mirrors Laravel ThemePresets). */

export const SUKOON_PURE_BLACK_LUXURY = {
  slug: "sukoon-pure-black-luxury",
  name: "Sukoon Pure Black Luxury",
  tokens: {
    primary: "#111827",
    secondary: "#1F2937",
    accent: "#000000",
    sidebar: "#111827",
    card: "#FFFFFF",
    border: "#E5E7EB",
    button: "#111827",
    hover: "#000000",
    link: "#111827",
    muted: "#6B7280",
    white: "#FFFFFF",
    section: "#F9FAFB",
  },
  typography: "Manrope, system-ui, -apple-system, sans-serif",
  border_radius: "16px",
  shadow_intensity: "soft",
};

/** @deprecated Legacy slug — same tokens as SUKOON_PURE_BLACK_LUXURY */
export const SUKOON_LUXURY_BLACK = {
  ...SUKOON_PURE_BLACK_LUXURY,
  slug: "sukoon-luxury-black",
};

export const MODERN_WHITE = {
  slug: "modern-white",
  name: "Modern White",
  tokens: {
    primary: "#111827",
    secondary: "#4B5563",
    accent: "#111827",
    sidebar: "#F9FAFB",
    card: "#FFFFFF",
    border: "#E5E7EB",
    button: "#111827",
    hover: "#000000",
    link: "#111827",
    muted: "#6B7280",
    white: "#FFFFFF",
    section: "#F3F4F6",
  },
  typography: "Manrope, system-ui, -apple-system, sans-serif",
  border_radius: "14px",
  shadow_intensity: "soft",
};

export const DARK_PREMIUM = {
  slug: "dark-premium",
  name: "Dark Premium",
  tokens: {
    primary: "#0F172A",
    secondary: "#1E293B",
    accent: "#FFFFFF",
    sidebar: "#020617",
    card: "#1E293B",
    border: "#334155",
    button: "#F8FAFC",
    hover: "#FFFFFF",
    link: "#F8FAFC",
    muted: "#94A3B8",
    white: "#F8FAFC",
    section: "#0F172A",
  },
  typography: "Manrope, system-ui, -apple-system, sans-serif",
  border_radius: "16px",
  shadow_intensity: "strong",
};

export const THEME_PRESETS = {
  [SUKOON_PURE_BLACK_LUXURY.slug]: SUKOON_PURE_BLACK_LUXURY,
  [SUKOON_LUXURY_BLACK.slug]: SUKOON_PURE_BLACK_LUXURY,
  [MODERN_WHITE.slug]: MODERN_WHITE,
  [DARK_PREMIUM.slug]: DARK_PREMIUM,
};

export const DEFAULT_THEME = SUKOON_PURE_BLACK_LUXURY;

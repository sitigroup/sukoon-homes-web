/**
 * Sukoon Trust Verification — light premium UI tokens (plugin-only).
 * Muted luxury gold (#B89A4A), graphite text (#1F2937), gold accent only where needed.
 */

/** Primary accent — muted luxury gold (less yellow than #C8A75B / #D4AF37) */
export const TV_GOLD = "#B89A4A";
export const TV_GOLD_RGB = "184, 154, 74";
/** Softer gold for progress bars / secondary accents */
export const TV_GOLD_SOFT = "#C5B896";
export const TV_GOLD_DARK = "#8F7840";
export const TV_GRAPHITE = "#1F2937";
export const TV_GRAPHITE_HOVER = "#111827";
export const TV_CHECK_BG = "#F5F3EA";
/** Recommended badge — ~12% lighter than solid gold fill */
export const TV_RECOMMENDED_BG = "rgba(184, 154, 74, 0.85)";

export const tvColors = {
  bg: {
    base: "#FFFFFF",
    elevated: "#F8F9FA",
    surface: "#FFFFFF",
    surfaceHover: "#F3F4F6",
  },
  text: {
    primary: "#111827",
    muted: "#6B7280",
    dim: "#9CA3AF",
    graphite: TV_GRAPHITE,
  },
  accent: TV_GOLD,
  accentMuted: `rgba(${TV_GOLD_RGB}, 0.08)`,
  accentBorder: `rgba(${TV_GOLD_RGB}, 0.28)`,
  border: "#E5E7EB",
  borderStrong: "#D1D5DB",
  success: "#059669",
  warning: "#D97706",
  danger: "#DC2626",
};

export const tvRadius = "16px";
export const tvShadow = "0 4px 20px rgba(0, 0, 0, 0.06)";
export const tvShadowAccent = `0 4px 20px rgba(${TV_GOLD_RGB}, 0.12), 0 0 0 1px rgba(${TV_GOLD_RGB}, 0.18)`;

/** Tailwind class bundles (included when src/plugins is in Tailwind content) */
export const tv = {
  page: "min-h-[50vh] bg-[#FFFFFF] text-[#111827]",
  section: "mx-auto w-full max-w-[1180px] px-4 py-6 sm:px-6 sm:py-8 lg:px-8",
  card: "rounded-[16px] border border-[#E5E7EB] bg-[#FFFFFF] shadow-[0_4px_20px_rgba(0,0,0,0.06)]",
  cardHover:
    "transition duration-200 hover:border-[rgba(184,154,74,0.32)] hover:bg-[#F3F4F6] hover:shadow-[0_8px_24px_rgba(0,0,0,0.08)]",
  heading: "text-[#111827] font-semibold tracking-tight",
  subheading: "text-[#6B7280]",
  muted: "text-[#6B7280] text-sm",
  accent: "text-[#B89A4A]",
  btnPrimary:
    "inline-flex items-center justify-center rounded-[16px] bg-[#1F2937] px-5 py-3 text-sm font-semibold text-[#FFFFFF] transition hover:bg-[#111827] disabled:opacity-50 shadow-[0_2px_8px_rgba(31,41,55,0.12)]",
  btnSecondary:
    "inline-flex items-center justify-center rounded-[16px] border border-[#E5E7EB] bg-[#FFFFFF] px-5 py-3 text-sm font-medium text-[#111827] transition hover:border-[rgba(184,154,74,0.35)] hover:bg-[#F3F4F6]",
  btnGhost:
    "inline-flex items-center justify-center rounded-[16px] px-4 py-2 text-sm font-medium text-[#6B7280] transition hover:bg-[#F3F4F6] hover:text-[#111827]",
  input:
    "w-full rounded-[12px] border border-[#E5E7EB] bg-[#FFFFFF] px-3 py-2.5 text-sm text-[#111827] placeholder:text-[#9CA3AF] outline-none focus:border-[rgba(184,154,74,0.45)] focus:ring-1 focus:ring-[rgba(184,154,74,0.15)]",
  badge:
    "inline-flex items-center gap-1.5 rounded-full border border-[rgba(184,154,74,0.22)] bg-[rgba(184,154,74,0.06)] px-3 py-1 text-xs font-medium text-[#8F7840]",
  divider: "border-[#E5E7EB]",
  strong: "font-semibold text-[#111827]",
};

export function cn(...parts) {
  return parts.filter(Boolean).join(" ");
}

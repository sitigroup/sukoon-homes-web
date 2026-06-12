/**
 * Full-site contrast overrides — neutralizes WRTeam hardcoded colors when theme is active.
 * Plugin-only; scoped under html[data-sukoon-theme-active="true"].
 */
const ROOT = 'html[data-sukoon-theme-active="true"]';

/** Dark containers where white/light text is correct */
const DARK_SURFACES = [
  ".primaryBg",
  ".brandBg",
  ".bg-black",
  ".bg-gray-900",
  ".bg-gray-800",
  '[class*="bg-primaryColor"]',
  '[class*="bg-\\[#111827"]',
  '[class*="bg-\\[#1F2937"]',
  '[class*="bg-\\[#000"]',
  "footer.bg-dark",
  ".sukoon-surface-dark",
  ".bgRed",
  '[class*="bg-emerald-8"]',
  '[class*="bg-black"]',
  ".btn-primary",
  '[class*="bg-\\[#1F2937\\]"]',
].join(", ");

export function buildSiteContrastCss() {
  return `
    /* ═══ Base typography ═══ */
    ${ROOT} body,
    ${ROOT} main,
    ${ROOT} #__next {
      color: var(--sukoon-text-primary, #111827);
      background-color: var(--sukoon-section, #F9FAFB);
    }

    ${ROOT} h1, ${ROOT} h2, ${ROOT} h3, ${ROOT} h4, ${ROOT} h5, ${ROOT} h6 {
      color: var(--sukoon-text-primary, #111827);
    }

    ${ROOT} p, ${ROOT} li, ${ROOT} span:not(.badge):not([class*="badge"]) {
      color: inherit;
    }

    /* ═══ WRTeam utility classes ═══ */
    ${ROOT} .brandColor,
    ${ROOT} .primaryColor,
    ${ROOT} .primaryTextColor,
    ${ROOT} .secondryTextColor {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    ${ROOT} .leadColor,
    ${ROOT} .descriptionColor {
      color: var(--sukoon-text-secondary, #6B7280) !important;
    }

    ${ROOT} .primaryBg,
    ${ROOT} .brandBg {
      background-color: var(--sukoon-button, #111827) !important;
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    ${ROOT} .primaryBorderColor,
    ${ROOT} .leadBorder {
      border-color: var(--sukoon-border, #E5E7EB) !important;
    }

    ${ROOT} .primaryBgLight,
    ${ROOT} .primaryCategoryBackground,
    ${ROOT} .brandBgLight,
    ${ROOT} .primaryBackgroundBg {
      background-color: var(--sukoon-section, #F9FAFB) !important;
      color: var(--sukoon-text-primary, #111827) !important;
    }

    ${ROOT} .brandBg.primaryTextColor,
    ${ROOT} .brandBg .primaryTextColor,
    ${ROOT} .primaryBg .primaryTextColor {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    /* ═══ Tailwind gray scale — bump readability ═══ */
    ${ROOT} .text-gray-300,
    ${ROOT} .text-gray-400 {
      color: var(--sukoon-text-secondary, #6B7280) !important;
      opacity: 1 !important;
    }

    ${ROOT} .text-gray-500,
    ${ROOT} .text-gray-600 {
      color: var(--sukoon-text-secondary, #6B7280) !important;
    }

    ${ROOT} .text-gray-700,
    ${ROOT} .text-gray-800,
    ${ROOT} .text-gray-900 {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    ${ROOT} .text-light {
      color: var(--sukoon-text-secondary, #6B7280) !important;
    }

    /* ═══ text-white reset on light surfaces ═══ */
    ${ROOT} .text-white {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    ${ROOT} .text-black {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    ${ROOT} [class*="text-[#FFFFFF]"],
    ${ROOT} [class*="text-[#ffffff]"],
    ${ROOT} [class*="text-[#fff]"] {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    ${ROOT} [class*="text-[#000000]"],
    ${ROOT} [class*="text-[#000]"] {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    ${ROOT} [class*="text-[#1F2937]"],
    ${ROOT} [class*="text-[#374151]"],
    ${ROOT} [class*="text-[#111827]"] {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    ${ROOT} [class*="text-[#6B7280]"],
    ${ROOT} [class*="text-[#9CA3AF]"],
    ${ROOT} [class*="text-[#D1D5DB]"] {
      color: var(--sukoon-text-secondary, #6B7280) !important;
    }

    ${ROOT} ${DARK_SURFACES} .text-white,
    ${ROOT} ${DARK_SURFACES} .text-black,
    ${ROOT} ${DARK_SURFACES} [class*="text-[#FFFFFF]"],
    ${ROOT} ${DARK_SURFACES} [class*="text-[#fff]"],
    ${ROOT} .primaryBg.text-white,
    ${ROOT} .brandBg.text-white,
    ${ROOT} .btn-primary,
    ${ROOT} .pagination .page-item.active .page-link {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    ${ROOT} ${DARK_SURFACES} a:not(.btn) {
      color: var(--sukoon-link-on-dark, #FFFFFF) !important;
    }

    /* ═══ Opacity traps ═══ */
    ${ROOT} .opacity-40,
    ${ROOT} .opacity-50,
    ${ROOT} .opacity-60,
    ${ROOT} .opacity-70,
    ${ROOT} [class*="disabled:opacity"] {
      opacity: 1 !important;
      color: var(--sukoon-text-secondary, #6B7280) !important;
    }

    ${ROOT} ${DARK_SURFACES} .opacity-50,
    ${ROOT} ${DARK_SURFACES} .opacity-60 {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
      opacity: 0.85 !important;
    }

    /* ═══ Legacy gold → monochrome ═══ */
    ${ROOT} [class*="text-[#B89A4A]"],
    ${ROOT} [class*="text-[#b89a4a]"],
    ${ROOT} [class*="text-[#8F7840]"],
    ${ROOT} [class*="text-[#8f7840]"],
    ${ROOT} [class*="text-[#C8A75B]"] {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    ${ROOT} [class*="ring-[#B89A4A]"],
    ${ROOT} [class*="border-[#B89A4A]"] {
      --tw-ring-color: var(--sukoon-border, #E5E7EB) !important;
      border-color: var(--sukoon-border, #E5E7EB) !important;
    }

    ${ROOT} [class*="bg-[rgba(184,154,74"] {
      background-color: var(--sukoon-section, #F9FAFB) !important;
    }

  /* ═══ Navbar / header / dropdown ═══ */
    ${ROOT} header,
    ${ROOT} nav,
    ${ROOT} .navbar,
    ${ROOT} [class*="Header"],
    ${ROOT} [class*="header"] {
      color: var(--sukoon-text-primary, #111827);
    }

    ${ROOT} header a:not(.btn),
    ${ROOT} nav a:not(.btn),
    ${ROOT} .navbar a:not(.btn) {
      color: var(--sukoon-link-on-light, #111827);
    }

    ${ROOT} .dropdown-menu,
    ${ROOT} [class*="dropdown"],
    ${ROOT} [role="menu"] {
      background-color: var(--sukoon-bg-primary, #FFFFFF) !important;
      color: var(--sukoon-text-primary, #111827) !important;
      border-color: var(--sukoon-border, #E5E7EB) !important;
    }

    ${ROOT} .dropdown-item,
    ${ROOT} [role="menuitem"] {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    ${ROOT} .dropdown-item:hover,
    ${ROOT} [role="menuitem"]:hover {
      background-color: var(--sukoon-section, #F9FAFB) !important;
      color: var(--sukoon-text-primary, #111827) !important;
    }

    /* ═══ Property cards / listings ═══ */
    ${ROOT} [class*="PropertyCard"],
    ${ROOT} [class*="property-card"],
    ${ROOT} [class*="ListingCard"],
    ${ROOT} .property-card,
    ${ROOT} .project-card {
      background-color: var(--sukoon-bg-primary, #FFFFFF) !important;
      color: var(--sukoon-text-primary, #111827) !important;
    }

    ${ROOT} [class*="PropertyCard"] *,
    ${ROOT} [class*="ListingCard"] * {
      color: inherit;
    }

    ${ROOT} [class*="PropertyCard"] .text-white,
    ${ROOT} [class*="ListingCard"] .text-white {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    /* ═══ Search / filters ═══ */
    ${ROOT} [class*="Filter"],
    ${ROOT} [class*="filter"],
    ${ROOT} [class*="SearchBox"],
    ${ROOT} [class*="SideFilter"] {
      color: var(--sukoon-text-primary, #111827);
    }

    ${ROOT} [class*="Filter"] label,
    ${ROOT} [class*="SideFilter"] label {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    /* ═══ Breadcrumbs ═══ */
    ${ROOT} .breadcrumb,
    ${ROOT} [class*="Breadcrumb"],
    ${ROOT} nav[aria-label*="breadcrumb" i] {
      color: var(--sukoon-text-secondary, #6B7280);
    }

    ${ROOT} .breadcrumb a,
    ${ROOT} [class*="Breadcrumb"] a {
      color: var(--sukoon-link-on-light, #111827) !important;
    }

    ${ROOT} .breadcrumb-item.active,
    ${ROOT} [aria-current="page"] {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    /* ═══ Chips / badges ═══ */
    ${ROOT} .badge,
    ${ROOT} [class*="chip"],
    ${ROOT} [class*="Chip"],
    ${ROOT} [class*="tag"] {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    ${ROOT} .badge.bg-primary,
    ${ROOT} .badge.bg-dark {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
      background-color: var(--sukoon-button, #111827) !important;
    }

    /* ═══ Profile / user sidebar ═══ */
    ${ROOT} [class*="UserSideBar"],
    ${ROOT} [class*="UserRoot"],
    ${ROOT} [class*="user-sidebar"],
    ${ROOT} [class*="UserDropDown"] {
      color: var(--sukoon-text-primary, #111827);
    }

    ${ROOT} [class*="UserSideBar"] .primaryBg,
    ${ROOT} [class*="UserDropDown"] .primaryBg {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    /* ═══ Trust Verification frontend (theme layer only) ═══ */
    ${ROOT} [class*="verification"],
    ${ROOT} [class*="Verification"],
    ${ROOT} [class*="my-verification"] {
      color: var(--sukoon-text-primary, #111827);
    }

    ${ROOT} [class*="verification"] .text-gray-500,
    ${ROOT} [class*="Verification"] .text-gray-500 {
      color: var(--sukoon-text-secondary, #6B7280) !important;
    }

    ${ROOT} [class*="verification"] [class*="text-[#B89A4A]"],
    ${ROOT} [class*="Verification"] [class*="text-[#B89A4A]"] {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    /* ═══ CMS / policy pages ═══ */
    ${ROOT} [class*="article"],
    ${ROOT} [class*="Article"],
    ${ROOT} [class*="cms"],
    ${ROOT} [class*="legal"],
    ${ROOT} [class*="privacy"],
    ${ROOT} [class*="terms"] {
      color: var(--sukoon-text-primary, #111827);
    }

    ${ROOT} [class*="article"] p,
    ${ROOT} [class*="Article"] p {
      color: var(--sukoon-text-primary, #111827);
    }

    /* ═══ Placeholders ═══ */
    ${ROOT} ::placeholder,
    ${ROOT} input::placeholder,
    ${ROOT} textarea::placeholder {
      color: var(--sukoon-placeholder, #9CA3AF) !important;
      opacity: 1 !important;
    }

    ${ROOT} input:focus,
    ${ROOT} select:focus,
    ${ROOT} textarea:focus {
      border-color: var(--sukoon-text-primary, #111827) !important;
      outline-color: var(--sukoon-text-primary, #111827);
    }

    /* ═══ Hover states ═══ */
    ${ROOT} a:hover:not(.btn):not([class*="btn"]) {
      color: var(--sukoon-text-primary, #111827);
      opacity: 0.88;
    }

    ${ROOT} ${DARK_SURFACES} a:hover:not(.btn) {
      color: var(--sukoon-link-on-dark, #FFFFFF) !important;
      opacity: 0.92;
    }

    /* ═══ Disabled ═══ */
    ${ROOT} :disabled,
    ${ROOT} [disabled],
    ${ROOT} .disabled,
    ${ROOT} [aria-disabled="true"] {
      color: var(--sukoon-text-secondary, #6B7280) !important;
      opacity: 0.78 !important;
    }

    /* ═══ Tables ═══ */
    ${ROOT} table,
    ${ROOT} .table {
      color: var(--sukoon-text-primary, #111827);
    }

    ${ROOT} table .small,
    ${ROOT} table .text-muted,
    ${ROOT} .table-helper {
      color: var(--sukoon-text-secondary, #6B7280) !important;
    }

    /* ═══ Theme Settings admin preview ═══ */
    ${ROOT} .st-admin,
    ${ROOT} .st-admin .st-card,
    ${ROOT} .st-preview,
    ${ROOT} .st-preview__card {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    ${ROOT} .st-preview__chrome,
    ${ROOT} .st-preview__chrome * {
      color: var(--sukoon-text-on-dark, #FFFFFF);
    }

    ${ROOT} .st-preview__chrome .st-preview__card,
    ${ROOT} .st-preview__chrome .st-preview__card * {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    /* ═══ Image overlay badges (property cards) ═══ */
    ${ROOT} [class*="absolute"][class*="bg-emerald"] .text-white,
    ${ROOT} [class*="absolute"][class*="bg-black"] .text-white,
    ${ROOT} [class*="absolute"].bg-emerald-800,
    ${ROOT} [class*="absolute"][class*="bg-black/"] {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }
  `;
}

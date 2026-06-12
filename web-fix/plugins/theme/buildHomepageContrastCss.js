/**
 * Homepage-only contrast fixes (/?lang=en).
 * Scoped: html[data-sukoon-theme-active="true"][data-sukoon-page="home"]
 */
const ROOT = 'html[data-sukoon-theme-active="true"][data-sukoon-page="home"]';

const DARK = [
  `${ROOT} .primaryBg`,
  `${ROOT} .brandBg`,
  `${ROOT} footer.brandBg`,
  `${ROOT} .bg-black`,
  `${ROOT} .bg-zinc-900`,
  `${ROOT} .bg-gray-900`,
  `${ROOT} .bg-gray-800`,
  `${ROOT} header .primaryBg`,
  `${ROOT} [class*="bg-\\[#1F2937"]`,
].join(", ");

export function buildHomepageContrastCss() {
  return `
    /* Legacy WRTeam CSS variables — homepage */
    ${ROOT} {
      --primary-color: var(--sukoon-button, #111827);
      --brand-color: var(--sukoon-text-primary, #111827);
      --lead-color: var(--sukoon-text-secondary, #6B7280);
      --primary-text-color02: var(--sukoon-text-secondary, #6B7280);
      --primary-text-color03: var(--sukoon-text-primary, #111827);
      --border-color: #E5E7EB;
      --new-border-color: #E5E7EB;
      --primary-background: var(--sukoon-section, #F9FAFB);
      --primary-rent: var(--sukoon-text-primary, #111827);
      --primary-rent-bg: var(--sukoon-section, #F3F4F6);
      --primary-sell: var(--sukoon-text-primary, #111827);
      --primary-sell-bg: var(--sukoon-section, #F3F4F6);
    }

    ${ROOT} body,
    ${ROOT} main,
    ${ROOT} #__next {
      color: var(--sukoon-text-primary, #111827);
      background-color: var(--sukoon-section, #F9FAFB);
    }

    /* Hero + search filters */
    ${ROOT} [class*="MainSwiper"],
    ${ROOT} [class*="LocationSearch"],
    ${ROOT} [class*="location-search"] {
      color: var(--sukoon-text-primary, #111827);
    }

    ${ROOT} [class*="MainSwiper"] .primaryColor {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    ${ROOT} input,
    ${ROOT} select,
    ${ROOT} textarea,
    ${ROOT} .form-control {
      color: var(--sukoon-text-primary, #111827) !important;
      background-color: var(--sukoon-bg-primary, #FFFFFF) !important;
      border-color: #E5E7EB !important;
    }

    ${ROOT} ::placeholder {
      color: #9CA3AF !important;
      opacity: 1 !important;
    }

    /* WRTeam utility classes */
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

    ${ROOT} .primaryBackgroundBg {
      background-color: var(--sukoon-section, #F9FAFB) !important;
      color: var(--sukoon-text-primary, #111827) !important;
    }

    ${ROOT} .primaryBgLight,
    ${ROOT} .primaryBgLight12,
    ${ROOT} .primaryCategoryBackground {
      background-color: var(--sukoon-section, #F9FAFB) !important;
    }

    ${ROOT} .brandBorder,
    ${ROOT} .leadBorder,
    ${ROOT} .newBorderColor,
    ${ROOT} .primaryBorderColor {
      border-color: #E5E7EB !important;
    }

    /* text-white: only flip on explicit light surfaces (not footer / project / overlays) */
    ${ROOT} .bg-white .text-white,
    ${ROOT} .card:not(.bg-zinc-900):not([class*="bg-black"]) .rounded-b-2xl .text-white,
    ${ROOT} [class*="CategoryCard"] .text-white {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    ${ROOT} ${DARK},
    ${ROOT} ${DARK} .text-white,
    ${ROOT} .primaryBg,
    ${ROOT} .brandBg,
    ${ROOT} [class*="bg-gray-900"],
    ${ROOT} [class*="bg-black"]:not(.bg-white) {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    ${ROOT} .primaryBg .primaryTextColor,
    ${ROOT} .brandBg .primaryTextColor,
    ${ROOT} .primaryBg.primaryTextColor {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    ${ROOT} .text-light {
      color: var(--sukoon-text-secondary, #6B7280) !important;
    }

    ${ROOT} .opacity-50,
    ${ROOT} .opacity-60,
    ${ROOT} .opacity-65,
    ${ROOT} .opacity-70 {
      opacity: 1 !important;
      color: var(--sukoon-text-secondary, #6B7280) !important;
    }

    ${ROOT} ${DARK} .opacity-50,
    ${ROOT} ${DARK} .opacity-65 {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
      opacity: 0.9 !important;
    }

    ${ROOT} .text-white\\/90,
    ${ROOT} [class*="text-white/"] {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    /* Property + category cards */
    ${ROOT} [class*="PropertyVertical"],
    ${ROOT} [class*="PropertyHorizontal"],
    ${ROOT} [class*="FeaturedProperty"],
    ${ROOT} [class*="PremiumProperty"],
    ${ROOT} [class*="CategoryCard"],
    ${ROOT} [class*="CityCard"],
    ${ROOT} .property-card {
      color: var(--sukoon-text-primary, #111827);
      background-color: var(--sukoon-bg-primary, #FFFFFF);
      border-color: #E5E7EB;
    }

    ${ROOT} [class*="PropertyVertical"] .rounded-b-2xl,
    ${ROOT} [class*="PropertyVertical"] .rounded-b-2xl h3 {
      color: var(--sukoon-text-primary, #111827);
    }

    ${ROOT} [class*="absolute"][class*="bg-black"] .text-white,
    ${ROOT} [class*="bg-black/"] .text-white,
    ${ROOT} [class*="bg-\\[#00000080\\]"] {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    /* Buttons */
    ${ROOT} .btn-primary,
    ${ROOT} .primaryBg,
    ${ROOT} .brandBg {
      background-color: var(--sukoon-button, #111827) !important;
      border-color: var(--sukoon-button, #111827) !important;
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    ${ROOT} .btn-outline,
    ${ROOT} .btn-light,
    ${ROOT} button.bg-white,
    ${ROOT} a.bg-white.brandColor,
    ${ROOT} .border.brandColor.bg-white {
      background-color: var(--sukoon-bg-primary, #FFFFFF) !important;
      color: var(--sukoon-text-primary, #111827) !important;
      border-color: #E5E7EB !important;
    }

    ${ROOT} .hover\\:brandBg:hover,
    ${ROOT} .hover\\:primaryBg:hover,
    ${ROOT} .hover\\:text-white:hover {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    ${ROOT} a.border.border-black,
    ${ROOT} button.border-black {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    ${ROOT} a.border.border-black:hover,
    ${ROOT} .hover\\:brandBg:hover.border-black {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    /* Dropdowns */
    ${ROOT} .dropdown-menu,
    ${ROOT} [role="menu"] {
      background-color: var(--sukoon-bg-primary, #FFFFFF) !important;
      color: var(--sukoon-text-primary, #111827) !important;
      border-color: #E5E7EB !important;
    }

    ${ROOT} .dropdown-item {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    /* Chips / badges */
    ${ROOT} .badge,
    ${ROOT} [class*="chip"],
    ${ROOT} figcaption.primaryBg {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    ${ROOT} .leadColor.badge,
    ${ROOT} span.leadColor:not(.primaryBg) {
      color: var(--sukoon-text-secondary, #6B7280) !important;
    }

    /* Dark homepage sections */
    ${ROOT} [class*="HomeNewSectionFour"],
    ${ROOT} [class*="HomePropertiesOnMap"] .bg-black,
    ${ROOT} .bg-black.text-white {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    ${ROOT} .bg-black .brandColor.bg-white,
    ${ROOT} .bg-black a.bg-white {
      color: var(--sukoon-text-primary, #111827) !important;
      background-color: var(--sukoon-bg-primary, #FFFFFF) !important;
    }

    /* Links (main content only — footer has its own rules below) */
    ${ROOT} main a:not(.btn):not([class*="btn-"]):not(.primaryBg):not(.brandBg) {
      color: var(--sukoon-link-on-light, #111827);
    }

    ${ROOT} main a:not(.btn):hover {
      color: var(--sukoon-text-primary, #111827);
    }

    /* Rent / Sell chips on property cards */
    ${ROOT} .primaryRentText,
    ${ROOT} .primarySellText,
    ${ROOT} span.primaryRentBg,
    ${ROOT} span.primarySellBg,
    ${ROOT} .primaryRentBg.primaryRentText,
    ${ROOT} .primarySellBg.primarySellText {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    ${ROOT} .primaryRentBg,
    ${ROOT} .primarySellBg,
    ${ROOT} span.primaryRentBg,
    ${ROOT} span.primarySellBg {
      background-color: var(--sukoon-section, #F3F4F6) !important;
      border: 1px solid #E5E7EB !important;
    }

    ${ROOT} [class*="absolute"] .text-white,
    ${ROOT} [class*="bg-black"] .text-white,
    ${ROOT} [class*="bg-\\[#00000080\\]"] .text-white {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    /* Legacy gold */
    ${ROOT} [class*="text-[#B89A4A]"],
    ${ROOT} [class*="text-[#8F7840]"] {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    /* ═══ FIX 1: Footer — dark brandBg, readable Quick Links / Property Listing ═══ */
    ${ROOT} footer.brandBg {
      background-color: var(--sukoon-bg-dark, #111827) !important;
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    ${ROOT} footer.brandBg h2,
    ${ROOT} footer.brandBg h3,
    ${ROOT} footer.brandBg .primaryTextColor,
    ${ROOT} footer.brandBg p,
    ${ROOT} footer.brandBg span,
    ${ROOT} footer.brandBg li,
    ${ROOT} footer.brandBg a,
    ${ROOT} footer.brandBg a.text-white,
    ${ROOT} footer.brandBg .hover-underline-animation,
    ${ROOT} footer.brandBg .text-white {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    ${ROOT} footer.brandBg a:hover,
    ${ROOT} footer.brandBg a.text-white:hover,
    ${ROOT} footer.brandBg .hover-underline-animation:hover {
      color: #E5E7EB !important;
    }

    ${ROOT} footer.brandBg .text-white\\/80,
    ${ROOT} footer.brandBg [class*="text-white/"] {
      color: rgba(255, 255, 255, 0.88) !important;
    }

    ${ROOT} footer.brandBg .bg-black {
      background-color: #000000 !important;
    }

    ${ROOT} footer.brandBg a[class*="bg-white"] .leadColor {
      color: var(--sukoon-text-secondary, #6B7280) !important;
    }

    ${ROOT} footer.brandBg a[class*="bg-white"] .brandColor {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    ${ROOT} footer.brandBg svg,
    ${ROOT} footer.brandBg .fill-white {
      fill: #FFFFFF !important;
      color: #FFFFFF !important;
    }

    /* ═══ FIX 2: Property / featured image overlays — 0.25–0.35 opacity ═══ */
    ${ROOT} .group .absolute.inset-0.bg-black.rounded-t-2xl {
      opacity: 0 !important;
    }

    ${ROOT} .group:hover .absolute.inset-0.bg-black.rounded-t-2xl,
    ${ROOT} .group:hover .absolute.inset-0.bg-black[class*="opacity-0"] {
      opacity: 0.3 !important;
    }

    ${ROOT} .group:hover .opacity-45,
    ${ROOT} .group-hover\\:opacity-45 {
      opacity: 0.32 !important;
    }

    ${ROOT} .bg-black.bg-opacity-75,
    ${ROOT} span.rounded-\\[8px\\].bg-black {
      --tw-bg-opacity: 0.3 !important;
      background-color: rgba(0, 0, 0, 0.3) !important;
    }

    ${ROOT} [class*="bg-\\[#00000080\\]"],
    ${ROOT} [class*="bg-\\[#00000090\\]"] {
      background-color: rgba(0, 0, 0, 0.3) !important;
    }

    ${ROOT} [class*="from-black\\/60"],
    ${ROOT} [class*="from-black/60"],
    ${ROOT} .bg-gradient-to-t[class*="from-black"] {
      --tw-gradient-from: rgb(0 0 0 / 0.3) var(--tw-gradient-from-position) !important;
      --tw-gradient-to: rgb(0 0 0 / 0) var(--tw-gradient-to-position) !important;
    }

    ${ROOT} [class*="from-black\\/60"].opacity-0,
    ${ROOT} .group:hover [class*="from-black\\/60"] {
      opacity: 0.35 !important;
    }

    /* ═══ FIX 3: Upcoming Projects — ProjectCardWithSwiper dark panel ═══ */
    ${ROOT} .rounded-t-2xl.bg-zinc-900,
    ${ROOT} .bg-zinc-900.p-4,
    ${ROOT} div.rounded-t-2xl.bg-zinc-900.text-white {
      background-color: var(--sukoon-bg-dark, #18181b) !important;
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    ${ROOT} div.rounded-t-2xl.bg-zinc-900.text-white h3,
    ${ROOT} div.rounded-t-2xl.bg-zinc-900.text-white h3.line-clamp-1,
    ${ROOT} .bg-zinc-900 h2,
    ${ROOT} .bg-zinc-900 h3,
    ${ROOT} .bg-zinc-900 .text-white,
    ${ROOT} .bg-zinc-900 p.font-normal.text-white {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
      opacity: 1 !important;
    }

    ${ROOT} .bg-zinc-900 p.text-white.opacity-65,
    ${ROOT} .bg-zinc-900 .opacity-65 {
      color: #E5E7EB !important;
      opacity: 1 !important;
    }

    ${ROOT} .bg-zinc-900 .leadColor.bg-white,
    ${ROOT} .bg-zinc-900 .rounded-lg.bg-white.leadColor,
    ${ROOT} .bg-zinc-900 div.flex.leadColor[class*="bg-white"] {
      background-color: #FFFFFF !important;
      color: var(--sukoon-text-primary, #111827) !important;
    }

    ${ROOT} .bg-zinc-900 .premiumBgColor,
    ${ROOT} .bg-zinc-900 .primaryBg.rounded {
      background-color: var(--sukoon-button, #111827) !important;
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    ${ROOT} .bg-zinc-900 .brandColor.px-1,
    ${ROOT} .bg-zinc-900 span.brandColor {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    ${ROOT} .bg-zinc-900 .leadColor.bg-white .brandColor {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    /* ═══ FIX 4: Featured properties dark band ═══ */
    ${ROOT} .bg-black.text-white,
    ${ROOT} [class*="HomeNewSectionFour"],
    ${ROOT} [class*="HomeNewSectionFour"] .text-white,
    ${ROOT} [class*="HomeNewSectionFour"] h2,
    ${ROOT} [class*="HomeNewSectionFour"] p {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    ${ROOT} [class*="HomeNewSectionFour"] a.bg-white,
    ${ROOT} [class*="HomeNewSectionFour"] .brandColor.bg-white {
      color: var(--sukoon-text-primary, #111827) !important;
      background-color: #FFFFFF !important;
    }

    /* ═══ FIX 5: Header / navbar polish (homepage only) ═══ */

    /* 1. Top contact strip (sibling of <header>, not inside it) */
    ${ROOT} div.primaryBg.text-white[class*="h-[48px]"],
    ${ROOT} div.flex.w-full.flex-col > div.primaryBg {
      background-color: var(--sukoon-bg-dark, #111827) !important;
      color: #FFFFFF !important;
    }

    ${ROOT} div.primaryBg.text-white a,
    ${ROOT} div.primaryBg.text-white a span,
    ${ROOT} div.primaryBg.text-white .ltr-number,
    ${ROOT} div.primaryBg.text-white .text-sm,
    ${ROOT} div.primaryBg.text-white span {
      color: #FFFFFF !important;
    }

    ${ROOT} div.primaryBg.text-white a.flex.items-center,
    ${ROOT} div.primaryBg.text-white .flex.items-center.gap-2,
    ${ROOT} div.primaryBg.text-white .flex.items-center.gap-4 {
      display: inline-flex !important;
      align-items: center !important;
    }

    ${ROOT} div.primaryBg.text-white svg,
    ${ROOT} div.primaryBg.text-white [class*="rounded-full"][class*="FFFFFF3D"] {
      color: #FFFFFF !important;
      fill: #FFFFFF !important;
      flex-shrink: 0;
      vertical-align: middle;
    }

    @media (max-width: 767px) {
      ${ROOT} div.primaryBg.text-white[class*="h-[48px]"] {
        display: block !important;
        height: auto !important;
        min-height: 44px;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
      }

      ${ROOT} div.primaryBg.text-white .container > div {
        flex-wrap: wrap;
        gap: 0.5rem 1rem;
        justify-content: center;
      }

      ${ROOT} div.primaryBg.text-white .language-dropdown {
        display: block !important;
      }
    }

    /* 2. Active nav — hide dot indicator, premium underline */
    ${ROOT} header ul.items-center li .flex.gap-1.mt-1,
    ${ROOT} header ul.items-center li > div.flex.gap-1 {
      display: none !important;
    }

    ${ROOT} header ul.hidden.xl\\:flex span.primaryColor.font-bold,
    ${ROOT} header ul.hidden.xl\\:flex span.font-bold.primaryColor {
      position: relative;
      display: inline-block;
      padding-bottom: 6px;
      color: #111827 !important;
    }

    ${ROOT} header ul.hidden.xl\\:flex span.primaryColor.font-bold::after,
    ${ROOT} header ul.hidden.xl\\:flex span.font-bold.primaryColor::after {
      content: "";
      position: absolute;
      left: 0;
      right: 0;
      bottom: 0;
      height: 2px;
      background: #111827;
      border-radius: 999px;
    }

    ${ROOT} header ul.hidden.xl\\:flex > li > a.flex.flex-col {
      gap: 0;
    }

    /* 3. Main navbar bar */
    ${ROOT} header.relative.z-50,
    ${ROOT} header.bg-white {
      background-color: #FFFFFF !important;
      border-bottom: 1px solid #E5E7EB;
    }

    ${ROOT} header a:not(.btn):not([class*="bg-"]) {
      color: var(--sukoon-link-on-light, #111827);
    }

    ${ROOT} header ul.hidden.xl\\:flex a,
    ${ROOT} header ul.hidden.xl\\:flex button.dropdown-trigger {
      color: #374151 !important;
    }

    ${ROOT} header ul.hidden.xl\\:flex a:hover,
    ${ROOT} header ul.hidden.xl\\:flex button.dropdown-trigger:hover {
      color: #111827 !important;
    }

    ${ROOT} header .text-gray-700,
    ${ROOT} header .text-gray-600 {
      color: #374151 !important;
    }

    ${ROOT} header .hover\\:primaryColor:hover {
      color: #111827 !important;
    }

    /* Header spacing balance */
    ${ROOT} header .container .my-3.flex.items-center.justify-between {
      gap: 0.75rem;
      min-height: 56px;
    }

    ${ROOT} header .flex.items-center.gap-6 {
      gap: clamp(0.75rem, 1.5vw, 1.5rem) !important;
      flex: 1 1 auto;
      min-width: 0;
    }

    ${ROOT} header .flex.items-center.gap-3 {
      gap: 0.75rem !important;
      flex-shrink: 0;
      align-items: center;
    }

    ${ROOT} header ul.hidden.xl\\:flex.items-center.gap-4 {
      gap: 1.25rem !important;
      margin-inline: 0.5rem;
    }

    ${ROOT} header .h-14.md\\:border-r {
      margin-inline: 0.25rem;
    }

    ${ROOT} header .rounded.bg-\\[\\#0000001A\\] {
      background-color: rgba(17, 24, 39, 0.08) !important;
    }

    ${ROOT} header .primaryBg.text-white.rounded-full,
    ${ROOT} header button.primaryBg,
    ${ROOT} header .dropdown-trigger .primaryBg {
      color: #FFFFFF !important;
    }

    /* 4. Language dropdown (top strip) */
    ${ROOT} .language-dropdown .absolute.rounded-md,
    ${ROOT} .language-dropdown div[class*="rounded-md"][class*="shadow-lg"] {
      border: 1px solid #D1D5DB !important;
      box-shadow: 0 4px 16px rgba(15, 23, 42, 0.1) !important;
    }

    ${ROOT} .language-dropdown .border-gray-100 {
      border-color: #D1D5DB !important;
    }

    ${ROOT} .language-dropdown div.cursor-pointer:hover,
    ${ROOT} .language-dropdown .group:hover span,
    ${ROOT} .language-dropdown .hover\\:primaryColor:hover {
      color: #111827 !important;
    }

    ${ROOT} .language-dropdown button.text-white {
      color: #FFFFFF !important;
      border: 1px solid rgba(255, 255, 255, 0.25);
    }

    /* 5. Profile / login trigger */
    ${ROOT} header .dropdown-trigger.flex.items-center,
    ${ROOT} header .xl\\:hidden .flex.items-center.gap-2 {
      align-items: center !important;
      gap: 0.5rem !important;
    }

    ${ROOT} header button.bg-gray-900,
    ${ROOT} header .hover\\:primaryBg.bg-gray-900 {
      background-color: #111827 !important;
      color: #FFFFFF !important;
    }

    /* Mobile sheet menu — active + language */
    ${ROOT} [data-radix-scroll-area-viewport] span.primaryColor,
    ${ROOT} [role="dialog"] .primaryBgLight.primaryColor {
      color: #111827 !important;
      background-color: #F3F4F6 !important;
    }
  `;
}

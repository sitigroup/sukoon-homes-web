import {
  MdApartment,
  MdAssignment,
  MdBusiness,
  MdCheckCircle,
  MdConstruction,
  MdDescription,
  MdGavel,
  MdHomeWork,
  MdLocationCity,
  MdOutlineVerifiedUser,
  MdShield,
  MdStorefront,
  MdSupportAgent,
  MdTerrain,
  MdVerifiedUser,
  MdVilla,
} from "react-icons/md";

/** Unified mega-menu column titles (Buy · Sell · Rent · Projects) */
export const MEGA_COL = {
  categories: "shMegaColCategories",
  popularAreas: "shMegaColPopularAreas",
  services: "shMegaColServices",
};

/**
 * POPULAR_AREAS — preview placeholders only (Phase 2 wiring).
 *
 * Future source: Area Wise Plugin (admin-managed locations).
 * Admin assigns city/area links into mega menu "Popular Areas" columns —
 * never as top-level nav items.
 *
 * Example future entries: "Property in Barmer", "Flats in Jodhpur",
 * "Plots in Jaipur", "Property in Mahaveer Nagar".
 */
export const POPULAR_AREAS = [
  { key: "shAreaBarmer", icon: MdLocationCity },
  { key: "shAreaJodhpur", icon: MdLocationCity },
  { key: "shAreaJaipur", icon: MdLocationCity },
  { key: "shAreaMahaveerNagar", icon: MdLocationCity },
];

export const NAV_SECTIONS = [
  {
    id: "buy",
    labelKey: "shNavBuy",
    featured: "buy",
    columns: [
      {
        titleKey: MEGA_COL.categories,
        accent: true,
        items: [
          { key: "shBuyApartments", icon: MdApartment },
          { key: "shBuyVillas", icon: MdVilla },
          { key: "shBuyPlots", icon: MdTerrain },
          { key: "shBuyCommercial", icon: MdStorefront },
          { key: "shBuyVerifiedListings", icon: MdVerifiedUser, highlight: true },
        ],
      },
      {
        titleKey: MEGA_COL.popularAreas,
        items: POPULAR_AREAS,
      },
      {
        titleKey: MEGA_COL.services,
        items: [
          { key: "shSvcKyc", icon: MdVerifiedUser },
          { key: "shSvcTrust", icon: MdCheckCircle },
          { key: "shSvcPolice", icon: MdGavel },
          { key: "shSvcPropertyDocs", icon: MdDescription },
        ],
      },
    ],
  },
  {
    id: "rent",
    labelKey: "shNavRent",
    featured: "rent",
    columns: [
      {
        titleKey: MEGA_COL.categories,
        accent: true,
        items: [
          { key: "shRentApartments", icon: MdApartment },
          { key: "shRentVillas", icon: MdVilla },
          { key: "shRentPG", icon: MdHomeWork },
          { key: "shRentCommercial", icon: MdStorefront },
          { key: "shRentAgreements", icon: MdAssignment },
        ],
      },
      {
        titleKey: MEGA_COL.popularAreas,
        items: POPULAR_AREAS,
      },
      {
        titleKey: MEGA_COL.services,
        items: [
          { key: "shRentTenantVerification", icon: MdOutlineVerifiedUser },
          { key: "shSvcKyc", icon: MdVerifiedUser },
          { key: "shSvcRentAgreement", icon: MdAssignment },
          { key: "shSvcPolice", icon: MdGavel },
        ],
      },
    ],
  },
  {
    id: "projects",
    labelKey: "shNavProjects",
    featured: "projects",
    columns: [
      {
        titleKey: MEGA_COL.categories,
        accent: true,
        items: [
          { key: "shProjOngoing", icon: MdHomeWork },
          { key: "shProjReadyToMove", icon: MdCheckCircle },
          { key: "shProjUnderConstruction", icon: MdConstruction },
          { key: "shProjCommercial", icon: MdBusiness },
        ],
      },
      {
        titleKey: MEGA_COL.popularAreas,
        items: POPULAR_AREAS,
      },
      {
        titleKey: MEGA_COL.services,
        items: [
          { key: "shProjVerification", icon: MdVerifiedUser },
          { key: "shProjDocumentation", icon: MdDescription },
          { key: "shSvcTrust", icon: MdCheckCircle },
          { key: "shSellOwnerAssistance", icon: MdSupportAgent },
        ],
      },
    ],
  },
  {
    id: "services",
    labelKey: "shNavServices",
    featured: "services",
    columns: [
      {
        titleKey: "shMegaColVerification",
        accent: true,
        items: [
          { key: "shSvcTenant", icon: MdOutlineVerifiedUser },
          { key: "shSvcOwner", icon: MdShield },
          { key: "shSvcPolice", icon: MdGavel },
          { key: "shFooterAgreements", icon: MdAssignment },
          { key: "shSvcTrust", icon: MdCheckCircle, highlight: true },
        ],
      },
      {
        titleKey: "shMegaColDocumentation",
        items: [
          { key: "shSvcKyc", icon: MdVerifiedUser },
          { key: "shSvcRentAgreement", icon: MdAssignment },
          { key: "shSvcPropertyDocs", icon: MdDescription },
        ],
      },
      {
        titleKey: "shMegaColTrustTools",
        items: [
          { key: "shBuyVerifiedListings", icon: MdVerifiedUser },
          { key: "shSvcAgreements", icon: MdAssignment },
          { key: "shSellOwnerVerification", icon: MdShield },
        ],
      },
    ],
  },
  {
    id: "about",
    labelKey: "shNavAbout",
    simple: true,
    items: [
      { key: "shAboutAbout", icon: MdHomeWork },
      { key: "shAboutContact", icon: MdDescription },
      { key: "shAboutPrivacy", icon: MdShield },
      { key: "shAboutTerms", icon: MdAssignment },
      { key: "shAboutDataDeletion", icon: MdGavel },
    ],
  },
];

const FEATURED_TITLE_KEYS = {
  buy: "shMegaFeaturedBuyTitle",
  rent: "shMegaFeaturedRentTitle",
  projects: "shMegaFeaturedProjTitle",
  services: "shMegaFeaturedSvcTitle",
};

export function getNavSection(id) {
  return NAV_SECTIONS.find((section) => section.id === id) ?? NAV_SECTIONS[0];
}

export function getFeaturedTitleKey(variant) {
  return FEATURED_TITLE_KEYS[variant] ?? "shMegaFeaturedBuyTitle";
}

export function getSectionDrawerLinks(section) {
  if (section.simple) {
    return section.items.map((item) => item.key);
  }
  const keys = section.columns.flatMap((column) => column.items.map((item) => item.key));
  return [...new Set(keys)];
}

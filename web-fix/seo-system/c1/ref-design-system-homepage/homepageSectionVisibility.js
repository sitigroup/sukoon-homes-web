/**
 * Homepage section visibility — preview defaults only.
 *
 * WRTeam admin already controls homepage section visibility in production.
 * This layer is not wired to admin/API yet; it exists so a future migration can
 * map admin homepage settings (or API fields) onto these keys via resolveHomepageSectionVisibility().
 */

export const defaultHomepageSectionVisibility = {
  hero: true,
  quickActions: true,
  featuredProperties: true,
  areaProperties: true,
  featuredProjects: true,
  trustVerification: true,
  rentalAgreements: true,
  policeVerification: true,
  whySukoon: true,
  popularCities: true,
  finalCta: true,
};

/**
 * Merge partial overrides onto preview defaults.
 * @param {Partial<typeof defaultHomepageSectionVisibility>} [overrides]
 * @returns {typeof defaultHomepageSectionVisibility}
 */
export function resolveHomepageSectionVisibility(overrides) {
  if (!overrides) {
    return { ...defaultHomepageSectionVisibility };
  }

  return { ...defaultHomepageSectionVisibility, ...overrides };
}

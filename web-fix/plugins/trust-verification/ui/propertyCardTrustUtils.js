/**
 * Property listing cards — owner-issued badge only (public).
 * Tenant SVT is profile / My Orders only, never on property cards.
 */

export function resolvePropertyCardTrustLabel(publicTrust) {
  if (!publicTrust || typeof publicTrust !== "object") {
    return null;
  }

  if (publicTrust.owner_trust_badge === true) {
    return "Trusted Owner";
  }

  return null;
}

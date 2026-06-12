/**
 * Strip admin-only score breakdown before rendering public trust UI.
 */
export function sanitizePublicTrust(trust) {
  if (!trust || typeof trust !== "object") {
    return trust;
  }

  const { breakdown, score_breakdown, score_breakdown_json, ...publicFields } = trust;
  return publicFields;
}

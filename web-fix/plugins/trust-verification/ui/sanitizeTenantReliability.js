/**
 * Allow-list owner-safe tenant reliability fields only.
 */
const ALLOWED_KEYS = new Set([
  "customer_id",
  "reliability_level",
  "reliability_label",
  "verification_completion",
  "verification_status",
  "overall_label",
  "checks",
  "last_calculated_at",
]);

export function sanitizeTenantReliability(data) {
  if (!data || typeof data !== "object") {
    return null;
  }

  const out = {};
  for (const key of ALLOWED_KEYS) {
    if (key in data) {
      out[key] = data[key];
    }
  }

  if (Array.isArray(out.checks)) {
    out.checks = out.checks.map((c) => ({
      key: c?.key,
      label: c?.label,
      verified: Boolean(c?.verified),
    }));
  }

  return out;
}

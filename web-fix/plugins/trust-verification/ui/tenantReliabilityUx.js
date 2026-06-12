/**
 * Owner-facing UX helpers — derive display from sanitized API only.
 * Never surface raw scores, percentages, or admin/risk fields.
 */

const POSITIVE_FACTOR_LABELS = {
  identity: "Identity verified",
  documents: "Documents approved",
  reference: "Reference checks completed",
  police: "Police verification completed",
};

export const RELIABILITY_TOOLTIP_TEXT =
  "This trust indicator is based on completed verification and trust activities within Sukoon Homes.";

export const RELIABILITY_PRIVACY_EXCLUDED = [
  "Religion",
  "Caste",
  "Personal chat content",
  "Personal private information",
  "Income display",
];

export function hasReliabilityData(reliability) {
  if (!reliability || typeof reliability !== "object") {
    return false;
  }
  if (typeof reliability.verification_completion === "number") {
    return true;
  }
  return Array.isArray(reliability.checks) && reliability.checks.length > 0;
}

/**
 * @returns {{ label: string, tone: 'high'|'medium'|'pending' }}
 */
export function getReliabilityBadge(reliability) {
  const level = reliability?.reliability_level || "";
  const completion = Number(reliability?.verification_completion ?? 0);
  const verifiedCount = (reliability?.checks || []).filter((c) => c?.verified).length;

  if (
    level === "premium_trusted_tenant" ||
    level === "trusted_tenant" ||
    completion >= 61 ||
    verifiedCount >= 3
  ) {
    return { label: "High Reliability", tone: "high" };
  }

  if (level === "verified_tenant" || completion >= 31 || verifiedCount >= 2) {
    return { label: "Medium Reliability", tone: "medium" };
  }

  return { label: "Verification Pending", tone: "pending" };
}

/** Positive factors only — no pending/failed items. */
export function getPositiveReliabilityFactors(reliability) {
  const checks = reliability?.checks || [];
  const factors = [];

  for (const check of checks) {
    if (!check?.verified) continue;
    const label = POSITIVE_FACTOR_LABELS[check.key] || null;
    if (label && !factors.includes(label)) {
      factors.push(label);
    }
  }

  if (
    reliability?.verification_status === "complete" &&
    !factors.includes("Verification completed")
  ) {
    factors.push("Verification completed");
  }

  return factors;
}

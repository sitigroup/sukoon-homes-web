"use client";

import SukoonTenantReliabilitySection from "./SukoonTenantReliabilitySection";

/** Resolve tenant/applicant customer id from API user objects. */
export function resolveApplicantCustomerId(applicant) {
  if (!applicant || typeof applicant !== "object") {
    return null;
  }
  const raw = applicant.id ?? applicant.user_id ?? applicant.customer_id;
  const id = Number(raw);
  return Number.isFinite(id) && id > 0 ? id : null;
}

/**
 * Owner/agent screening panel — shows compact tenant reliability for an applicant.
 */
export default function TenantApplicantReliabilityPanel({ applicant, customerId, className = "" }) {
  const resolvedId = customerId ?? resolveApplicantCustomerId(applicant);
  if (!resolvedId) {
    return null;
  }
  return <SukoonTenantReliabilitySection customerId={resolvedId} className={className} />;
}

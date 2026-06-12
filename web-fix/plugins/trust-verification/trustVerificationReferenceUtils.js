/** Reference check — types, statuses, helpers (Task 14). */

export const REFERENCE_TYPES = [
  { id: "previous_landlord", label: "Previous landlord" },
  { id: "employer", label: "Employer" },
  { id: "family_reference", label: "Family reference" },
];

export const REFERENCE_STATUS_LABELS = {
  pending: "Pending",
  contacted: "Contacted",
  verified: "Verified",
  failed: "Failed",
  no_response: "No response",
};

export const EMPTY_REFERENCE = {
  reference_type: "previous_landlord",
  name: "",
  relation: "",
  mobile: "",
  email: "",
  notes: "",
};

export function packageIncludesReferenceCheck(pkg) {
  const features = pkg?.features || [];
  return features.some((f) => {
    const key = typeof f === "string" ? f : f?.key;
    if (key !== "reference_check") return false;
    if (typeof f === "object" && f?.included === false) return false;
    return true;
  });
}

export function referenceTypeLabel(type) {
  return REFERENCE_TYPES.find((t) => t.id === type)?.label || type;
}

export function referenceStatusLabel(status) {
  return REFERENCE_STATUS_LABELS[status] || status;
}

export function orderNeedsReferenceSubmission(order) {
  if (!order?.reference_check_enabled) return false;
  const progress = order.reference_progress;
  if (!progress?.required) return false;
  return progress.status === "pending_submission";
}

export function orderHasReferenceProgress(order) {
  return Boolean(order?.reference_check_enabled && order?.reference_progress?.required);
}

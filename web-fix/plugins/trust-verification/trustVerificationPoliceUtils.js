/** Rajasthan Police verification helpers (Task 15). */

export const POLICE_STATUS_LABELS = {
  not_submitted: "Not submitted",
  submitted: "Submitted",
  under_review: "Under review",
  completed: "Completed",
  rejected: "Rejected",
  need_more_documents: "Need more documents",
};

export const EMPTY_POLICE_FORM = {
  police_station_name: "",
  city: "",
  district: "",
  applicant_mobile: "",
  reference_number: "",
  customer_notes: "",
};

const POLICE_CHECK_KEYS = ["criminal_check", "police_verification", "tenant_verification"];
const KEYWORD_HINTS = ["police", "criminal", "civil"];

export function packageIncludesPoliceVerification(pkg) {
  const features = pkg?.features || [];
  return features.some((f) => {
    const key = String(typeof f === "string" ? f : f?.key || "").toLowerCase();
    const label = String(typeof f === "object" ? f?.label || "" : "").toLowerCase();
    if (typeof f === "object" && f?.included === false) return false;
    if (POLICE_CHECK_KEYS.includes(key)) return true;
    return KEYWORD_HINTS.some((w) => key.includes(w) || label.includes(w));
  });
}

export function orderHasPoliceVerification(order) {
  return Boolean(order?.police_verification_enabled);
}

export function policeStatusLabel(status) {
  return POLICE_STATUS_LABELS[status] || status;
}

export function isPoliceFormComplete(form) {
  return (
    form?.police_station_name?.trim() &&
    form?.city?.trim() &&
    form?.district?.trim() &&
    /^\d{10}$/.test(String(form?.applicant_mobile || "").replace(/\D/g, ""))
  );
}

export function orderCanSubmitPolice(order) {
  if (!orderHasPoliceVerification(order)) return false;
  if (!["submitted", "in_progress"].includes(String(order?.status || ""))) return false;
  const pv = order?.police_verification;
  return !pv || !["completed"].includes(pv.status);
}

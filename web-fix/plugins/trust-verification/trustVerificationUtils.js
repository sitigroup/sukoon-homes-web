export const TRUST_VERIFICATION_DEFAULT_CITY = {
  slug: "barmer",
  label: "Barmer",
};

/** @deprecated use resolveCity() */
export const TRUST_VERIFICATION_CITY = TRUST_VERIFICATION_DEFAULT_CITY;

export const slugifyCity = (name) =>
  String(name || "")
    .trim()
    .toLowerCase()
    .replace(/[^\w\s-]/g, "")
    .replace(/\s+/g, "-")
    .replace(/-+/g, "-");

export const humanizeSlug = (slug) =>
  String(slug || "")
    .split("-")
    .filter(Boolean)
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(" ");

/**
 * @param {string} citySlugRaw
 * @param {Array<{slug:string,label:string}>} knownCities from API
 */
export const resolveCity = (citySlugRaw = "", knownCities = []) => {
  const raw = slugifyCity(citySlugRaw);

  if (knownCities.length) {
    const match = knownCities.find((c) => c.slug === raw);
    if (match) {
      return { ...match, unknown: false };
    }
    if (!raw) {
      return { ...knownCities[0], unknown: false };
    }
    return { slug: raw, label: humanizeSlug(raw), unknown: true };
  }

  if (raw === TRUST_VERIFICATION_DEFAULT_CITY.slug) {
    return { ...TRUST_VERIFICATION_DEFAULT_CITY, unknown: false };
  }
  if (raw) {
    return { slug: raw, label: humanizeSlug(raw), unknown: false };
  }
  return { ...TRUST_VERIFICATION_DEFAULT_CITY, unknown: false };
};

export const resolvePropertyCitySlug = (property) => {
  const name =
    property?.area_listing?.city_name ||
    property?.city_name ||
    property?.city ||
  "";
  return slugifyCity(name);
};

export const verificationWizardPath = (orderType, citySlug, lang = "en") => {
  const q = lang ? `?lang=${lang}` : "";
  return `/${orderType}-verification-in-${citySlug}${q}`;
};

export const formatInr = (amount, symbol = "₹") =>
  `${symbol}${Number(amount || 0).toLocaleString("en-IN")}`;

/** Truthy check for API boolean/1 values. */
export function tvTruthy(value) {
  return value === true || value === 1 || value === "1";
}

/** Customer may download PDF (top-level flag or nested report fields). */
export function canDownloadTrustVerificationReport(order) {
  if (!order) return false;
  if (tvTruthy(order.can_download_report)) return true;
  const report = order.report;
  if (!report) return false;
  return (
    tvTruthy(report.download_available) ||
    tvTruthy(report.has_report_file) ||
    tvTruthy(report.uploaded)
  );
}

/** Completed + paid but PDF missing on server. */
export function isTrustVerificationReportAwaiting(order) {
  if (!order || canDownloadTrustVerificationReport(order)) return false;
  if (tvTruthy(order.report?.awaiting_upload)) return true;
  const payment = String(order.payment_status || "pending").toLowerCase();
  return order.status === "completed" && ["paid", "waived"].includes(payment);
}

/** Normalize list/detail API rows so UI flags stay consistent. */
export function normalizeTrustVerificationOrder(order) {
  if (!order) return order;
  const canDownload = canDownloadTrustVerificationReport(order);
  const awaiting = isTrustVerificationReportAwaiting({
    ...order,
    can_download_report: canDownload,
    report: order.report
      ? { ...order.report, download_available: canDownload, uploaded: canDownload || order.report.uploaded }
      : order.report,
  });
  return {
    ...order,
    can_download_report: canDownload,
    report: order.report
      ? {
          ...order.report,
          download_available: canDownload,
          has_report_file: canDownload || tvTruthy(order.report.has_report_file),
          uploaded: canDownload || tvTruthy(order.report.uploaded),
          awaiting_upload: awaiting,
        }
      : order.report,
  };
}

export const verificationCopy = {
  tenant: {
    title: (city) => `Online Tenant Verification in ${city}`,
    lead: "Ensure safety before handing over keys — background checks with a detailed PDF report delivered by email.",
    subjectLabel: "Tenant",
    cta: "Submit tenant verification request",
  },
  owner: {
    title: (city) => `Online Owner Verification in ${city}`,
    lead: "Confirm the landlord is genuine before you pay token or rent — ownership and identity checks with a PDF report.",
    subjectLabel: "Owner / landlord",
    cta: "Submit owner verification request",
  },
};

import { TRUST_VERIFICATION_CONTENT_FALLBACK } from "./trustVerificationContentFallback";

function mergeObjects(base, patch) {
  if (!patch || typeof patch !== "object") return base;
  const out = { ...base };
  Object.keys(patch).forEach((key) => {
    const val = patch[key];
    if (val === undefined || val === null || val === "") return;
    if (Array.isArray(val) && val.length === 0) return;
    out[key] = val;
  });
  return out;
}

/** Merge API CMS payload over static fallback (per-block safe). */
export function mergeTrustVerificationContent(apiData) {
  const fb = TRUST_VERIFICATION_CONTENT_FALLBACK;
  if (!apiData || typeof apiData !== "object") return fb;

  return {
    hub: mergeObjects(fb.hub, apiData.hub),
    wizard: mergeObjects(fb.wizard, apiData.wizard),
    report: mergeObjects(fb.report, apiData.report),
    faq:
      Array.isArray(apiData.faq) && apiData.faq.length
        ? apiData.faq.map((item) => ({
            question: item.question || "",
            answer: item.answer || "",
          }))
        : fb.faq,
    testimonials:
      Array.isArray(apiData.testimonials) && apiData.testimonials.length
        ? apiData.testimonials
        : fb.testimonials,
    legal: {
      terms: apiData.legal?.terms || fb.legal.terms,
      privacy: apiData.legal?.privacy || fb.legal.privacy,
      refund: apiData.legal?.refund || fb.legal.refund,
    },
  };
}

export function applyCityTemplate(template, cityLabel = "") {
  return String(template || "").replace(/\{city\}/g, cityLabel);
}

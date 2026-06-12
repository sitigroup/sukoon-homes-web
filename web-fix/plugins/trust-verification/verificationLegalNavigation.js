const LEGAL_PATHS = {
  privacy: "/verification-privacy",
  terms: "/verification-terms",
  refund: "/verification-refund-policy",
};

/**
 * @param {string} pagePath
 * @param {{ lang?: string, returnTo?: string }} [opts]
 */
export function buildVerificationLegalHref(pagePath, { lang = "en", returnTo } = {}) {
  const params = new URLSearchParams();
  if (lang) params.set("lang", lang);
  if (returnTo) {
    const raw = Array.isArray(returnTo) ? returnTo[0] : returnTo;
    if (raw) params.set("return", String(raw));
  }
  const qs = params.toString();
  return qs ? `${pagePath}?${qs}` : pagePath;
}

/**
 * @param {string|string[]|undefined} returnParam
 * @param {string} [lang]
 */
export function resolveVerificationLegalBackHref(returnParam, lang = "en") {
  const raw = Array.isArray(returnParam) ? returnParam[0] : returnParam;
  if (typeof raw === "string" && raw.trim()) {
    try {
      const decoded = decodeURIComponent(raw.trim());
      if (decoded.startsWith("/") && !decoded.startsWith("//")) {
        return decoded;
      }
    } catch {
      /* use fallback */
    }
  }
  return `/verification?lang=${lang || "en"}`;
}

/** @param {string} [lang] */
export function myVerificationOrdersReturnPath(lang = "en") {
  return `/my-verification-orders?lang=${lang}`;
}

/** @param {string} [lang] */
export function verificationHubReturnPath(lang = "en") {
  return `/verification?lang=${lang}`;
}

/**
 * Current page path for policy return links (wizard, hub, account, etc.).
 * @param {import("next/router").NextRouter} router
 */
export function buildReturnPathFromRouter(router) {
  const asPath = router?.asPath;
  if (typeof asPath === "string" && asPath.startsWith("/") && !asPath.startsWith("//")) {
    return asPath.split("#")[0];
  }
  const lang = router?.query?.lang || "en";
  return verificationHubReturnPath(lang);
}

/**
 * @param {string} [lang]
 * @param {string} [returnTo] — path to return to after reading policy (e.g. /my-verification-orders?lang=en)
 */
export function trustVerificationLegalLinks(lang = "en", returnTo) {
  return [
    { label: "Privacy Policy", href: buildVerificationLegalHref(LEGAL_PATHS.privacy, { lang, returnTo }) },
    { label: "Verification Terms", href: buildVerificationLegalHref(LEGAL_PATHS.terms, { lang, returnTo }) },
    { label: "Refund Policy", href: buildVerificationLegalHref(LEGAL_PATHS.refund, { lang, returnTo }) },
  ];
}

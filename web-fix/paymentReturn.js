/** Remember where to send the user after package payment (add property/project, etc.) */
const PAYMENT_RETURN_KEY = "sukoon_payment_return_url";

export const setPaymentReturnUrl = (path) => {
  if (typeof window === "undefined" || !path) return;
  sessionStorage.setItem(PAYMENT_RETURN_KEY, path);
};

export const consumePaymentReturnUrl = () => {
  if (typeof window === "undefined") return null;
  const path = sessionStorage.getItem(PAYMENT_RETURN_KEY);
  if (path) {
    sessionStorage.removeItem(PAYMENT_RETURN_KEY);
  }
  return path;
};

export const getPostPaymentRedirect = (router, packageData = null) => {
  const stored = consumePaymentReturnUrl();
  if (stored) {
    return stored;
  }

  const lang = router?.query?.lang || "en";
  const isAgent =
    packageData?.user_type === "agent" || router?.asPath?.includes("/agent/");

  return isAgent
    ? `/agent/dashboard?lang=${lang}`
    : `/user/dashboard?lang=${lang}`;
};

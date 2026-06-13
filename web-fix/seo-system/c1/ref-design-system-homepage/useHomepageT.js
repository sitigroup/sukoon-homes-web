import { useRouter } from "next/router";

import en from "./i18n/en";
import hi from "./i18n/hi";

const catalogs = { en, hi };

export default function useHomepageT(langOverride) {
  const router = useRouter();
  const lang =
    langOverride ||
    (typeof router?.query?.lang === "string" ? router.query.lang : "en");
  const catalog = catalogs[lang] || catalogs.en;

  return (key) => catalog[key] || catalogs.en[key] || key;
}

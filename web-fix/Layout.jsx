import dynamic from "next/dynamic";
import * as api from "@/api/apiRoutes";
import { useCallback, useEffect, useRef, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { setWebSettings } from "@/redux/slices/webSettingSlice";
import {
  setActiveLanguage,
  setCurrentLanguage,
  setDefaultLanguage,
  setIsFetched,
  setLanguages, setIsLanguageLoaded
} from "@/redux/slices/languageSlice";
import { useRouter } from "next/router";
import { setCategories, setInitialLoadComplete } from "@/redux/slices/cacheSlice";
import withAuth from "../HOC/withAuth";
import Header from "./Header";
import Footer from "./Footer";
import FullScreenSpinLoader from "../ui/loaders/FullScreenSpinLoader";
// To suppress hydration error
const PushNotificationLayout = dynamic(
  () => import("../wrapper/PushNotificationLayout"),
  { ssr: false },
);

import { useTranslation } from "../context/TranslationContext";
import CookieComponent from "../cookie/Cookie";
import PWAInstallButton from "../PWAInstallButton";
import UnderMaintenance from "../under-maintenance/UnderMaintenance";
import { useQuery } from "@tanstack/react-query";
import toast from "react-hot-toast";
import SomethingWentWrong from "../error/SomethingWentWrong";
import { updateUserProfile } from "@/redux/slices/authSlice";

const Layout = ({ children }) => {
  const router = useRouter();
  const t = useTranslation()
  const dispatch = useDispatch();
  const [isInitialLoading, setIsInitialLoading] = useState(false); // Only for initial app load
  const [isRouteChanging, setIsRouteChanging] = useState(false);
  const pendingLangReplace = useRef(null);
  const [isError, setIsError] = useState(false);
  const isLoadCompleted = useSelector((state) => state.cacheData.initialLoadComplete); // Track if initial load finished

  // Get language settings from Redux
  const defaultLanguage = useSelector((state) => state.LanguageSettings?.default_language);
  const activeLanguage = useSelector((state) => state.LanguageSettings?.active_language);
  const isFetched = useSelector((state) => state.LanguageSettings?.isFetched);
  const isLanguageLoaded = useSelector((state) => state.LanguageSettings?.isLanguageLoaded);
  const currentLanguage = useSelector((state) => state.LanguageSettings?.current_language);
  const availableLanguages = useSelector((state) => state.LanguageSettings?.languages);
  const webSettings = useSelector((state) => state.WebSetting?.data);
  const userData = useSelector((state) => state.User?.data);
  const currentRole = useSelector((state) => state?.User)?.role
  const underMaintenance = webSettings?.web_maintenance_mode === "1";
  const allowCookies = webSettings?.allow_cookies;

  // Get locale from router query params
  const urlLocale = router.query?.lang;

  const isUserListingPages = router?.asPath?.startsWith("/user/")

  // Track route transitions so we do not call router.replace during getServerSideProps fetch
  useEffect(() => {
    const onStart = () => setIsRouteChanging(true);
    const onEnd = () => {
      setIsRouteChanging(false);
      if (pendingLangReplace.current) {
        const replaceOptions = pendingLangReplace.current;
        pendingLangReplace.current = null;
        router.replace(replaceOptions, undefined, { shallow: true });
      }
    };

    router.events.on("routeChangeStart", onStart);
    router.events.on("routeChangeComplete", onEnd);
    router.events.on("routeChangeError", onEnd);

    return () => {
      router.events.off("routeChangeStart", onStart);
      router.events.off("routeChangeComplete", onEnd);
      router.events.off("routeChangeError", onEnd);
    };
  }, [router]);

  // Memoized API calls to prevent unnecessary re-renders
  const fetchWebSettings = useCallback(async () => {
    try {
      const response = await api.getWebSetting();
      const { data } = response;
      document.documentElement.lang = currentLanguage?.code;
      document.documentElement.style.setProperty(
        "--primary-color",
        data?.system_color
      );
      document.documentElement.style.setProperty(
        "--primary-category-background",
        data?.category_background
      );
      document.documentElement.style.setProperty(
        "--primary-sell",
        data?.sell_web_color
      );
      document.documentElement.style.setProperty(
        "--primary-rent",
        data?.rent_web_color
      );
      document.documentElement.style.setProperty(
        "--primary-sell-bg",
        data?.sell_web_background_color
      );
      document.documentElement.style.setProperty(
        "--primary-rent-bg",
        data?.rent_web_background_color
      );
      document.querySelectorAll("link[rel='icon']").forEach((link) => {
        link.href = data?.web_favicon;
      });

      dispatch(setWebSettings({ data }));
      dispatch(setLanguages({ data: data.languages }));
      dispatch(setDefaultLanguage({ data: data.default_language }));
      document.dir = currentLanguage?.rtl === 1 ? "rtl" : "ltr";

      return true;
    } catch (error) {
      console.error("Failed to fetch web settings:", error);
      setIsError(true);
      return false;
    }
  }, [dispatch, currentLanguage]);

  const fetchCategories = useCallback(async () => {
    try {
      const response = await api.getCategoriesApi({ limit: "12", offset: "0" });
      dispatch(setCategories({ data: response.data }));
      return true;
    } catch (error) {
      console.error("Failed to fetch categories:", error);
      return false;
    }
  }, [dispatch]);

  const fetchLanguageData = useCallback(
    async (localeCode) => {
      if (!localeCode) return false;

      // Skip if this is the current active language and data is already loaded
      if (localeCode === activeLanguage && isLanguageLoaded) {
        return true;
      }

      try {
        const response = await api.getLanguageData({
          language_code: localeCode,
          web_language_file: 1,
        });
        if (response?.data?.rtl === 1) {
          document.dir = "rtl";
        } else {
          document.dir = "ltr";
        }

        document.documentElement.lang = localeCode;
        if (userData?.id && localeCode) {
          await changeNotificationLanguage(localeCode);
        }

        // Set active language to the requested locale
        dispatch(setActiveLanguage({ data: localeCode }));
        dispatch(setCurrentLanguage({ data: response.data }));
        dispatch(setIsFetched({ data: true }));

        // Also fetch categories to ensure they're updated with new language
        await fetchCategories();

        // Finally mark language as loaded after all dependent data is refreshed
        dispatch(setIsLanguageLoaded({ data: true }));

        return true;
      } catch (error) {
        console.error(
          `Failed to fetch language data for ${localeCode}:`,
          error,
        );
        return false;
      }
    },
    [dispatch, activeLanguage, isLanguageLoaded, fetchCategories, userData?.id],
  );

  // Fetch web settings using React Query for caching and stale time
  const webSettingsQuery = useQuery({
    queryKey: ['webSettings'],
    queryFn: fetchWebSettings,
    // keepPreviousData: true,
    staleTime: 0, // 0 minutes
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,
    refetchOnMount: false,
  })


  // Fetch User Data
  const fetchUserData = async () => {
    try {
      const res = await api.getUserProfileApi();
      if (res.data) {
        // Update Redux store with the fetched user data
        dispatch(
          updateUserProfile({
            data: res?.data,
          }),
        );
        return res?.data;
      }
    } catch (error) {
      console.error("Error fetching user data:", error);
    }
  };

  const fetchUserProfileQuery = useQuery({
    queryKey: ['userProfile'],
    queryFn: fetchUserData,
    enabled: !!userData?.id, // Only fetch if user ID is available
    staleTime: 0,
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,
    refetchOnMount: false,
  })

  const changeNotificationLanguage = async (language_code) => {
    try {
      const res = await api.updateNotificationLanguageApi({ language_code });
      return res?.data;
    } catch (error) {
      console.error('Failed to update notification language:', error);
      return null;
    }
  }

  

  useEffect(() => {
    if (userData?.id && isLanguageLoaded) {
      const code = (currentLanguage?.code || activeLanguage || defaultLanguage || "").trim();
      const isSupported =
        !availableLanguages?.length ||
        availableLanguages.some((lang) => lang.code === code);
      if (code && isSupported) {
        changeNotificationLanguage(code);
      }
    }

    // Allow agents to view their own property/project detail pages via Layout
    const isAgentDetailRoute =
      router?.pathname?.startsWith('/agent/my-property') ||
      router?.pathname?.startsWith('/agent/my-project');

    if (currentRole === "agent" && !isAgentDetailRoute) {
      toast.error(t("pleaseSwitchToUser"))
      router?.replace(`/agent/dashboard?lang=${activeLanguage}`)
    }
  }, [userData?.id, isLanguageLoaded, currentLanguage?.code, activeLanguage, defaultLanguage, availableLanguages])

  // Handle language query parameter initialization and validation
  useEffect(() => {
    const handleLanguageFromUrl = async () => {
      if (!router.isReady || router.isFallback) return;
      if (!availableLanguages || availableLanguages.length === 0) return;

      const lang = typeof router.query.lang === "string" ? router.query.lang : "";
      let shouldUpdateUrl = false;
      let langToUse = currentLanguage?.code || defaultLanguage || "en";

      if (!lang) {
        shouldUpdateUrl = true;
      } else if (!availableLanguages.some((l) => l.code === lang)) {
        shouldUpdateUrl = true;
      } else if (lang !== activeLanguage) {
        langToUse = lang;
        shouldUpdateUrl = false;
      }

      if (!shouldUpdateUrl || router.query.lang === langToUse) {
        return;
      }

      const replaceOptions = {
        pathname: router.pathname,
        query: { ...router.query, lang: langToUse },
      };

      try {
        if (isRouteChanging) {
          pendingLangReplace.current = replaceOptions;
          return;
        }

        router.replace(replaceOptions, undefined, { shallow: true });
      } catch (error) {
        console.error("Error handling language parameters:", error);
      }
    };

    handleLanguageFromUrl();
  }, [
    router.isReady,
    router.isFallback,
    router.pathname,
    router.query.lang,
    router.query.slug,
    currentLanguage?.code,
    defaultLanguage,
    availableLanguages,
    activeLanguage,
    isRouteChanging,
  ]);

  // Fetch language data on initial load, manual change or language inactive conflict
  useEffect(() => {
    const loadLanguageData = async () => {
      // Skip if no language code available yet
      if (!urlLocale) return;

      // Only show loading for initial app load
      if (!isLoadCompleted) {
        setIsInitialLoading(true);
      }
      try {
        await fetchLanguageData(urlLocale);
      } catch (error) {
        console.error("Error loading language data:", error);
        setIsError(true);
      } finally {
        dispatch(setInitialLoadComplete(true));
        setIsInitialLoading(false);
      }
    };

    loadLanguageData();

    return () => { };
  }, [urlLocale, isLoadCompleted]);


  // Show loader for initial load or route changes
  const isPageNotReady =
    isInitialLoading ||
    webSettingsQuery.isLoading ||
    !isLanguageLoaded ||
    !isFetched ||
    !isLoadCompleted;

  if (isPageNotReady) {
    return <FullScreenSpinLoader />;
  }

  if (underMaintenance) {
    return (
      <UnderMaintenance />
    )
  }

  if (isError) {
    return <SomethingWentWrong />;
  }

  return (
    <>
      <Header />
      <main className={`h-full ${isUserListingPages ? "" : "min-h-screen"} w-full`}>{children}</main>
      <Footer />
      {allowCookies && <CookieComponent />}
      {process.env.NEXT_PUBLIC_PWA_ENABLED === "true" && (
        <PWAInstallButton />
      )}
    </>
  );
};

export default withAuth(Layout);

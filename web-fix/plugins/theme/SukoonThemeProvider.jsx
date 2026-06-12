"use client";

import { createContext, useCallback, useContext, useEffect, useMemo, useState, startTransition } from "react";
import TrustUiHydrationGate from "@/plugins/trust-verification/ui/TrustUiHydrationGate";
import { useQuery } from "@tanstack/react-query";
import { fetchPublicTheme } from "./themeApi";
import { applyThemeVariables, clearThemeStyles } from "./applyThemeVariables";
import { DEFAULT_THEME } from "./themePresets";
import { normalizeThemePayload } from "./themeTokens";
import {
  applyHomepageLegacyVarBridge,
  syncHomepagePageMarker,
} from "./homepageThemeBridge";
import { useRouter } from "next/router";

const SukoonThemeContext = createContext({
  theme: DEFAULT_THEME,
  isPublished: false,
  isLoading: true,
  refreshTheme: () => {},
});

export function SukoonThemeProvider({ children, webSettings = null }) {
  const [applied, setApplied] = useState(false);
  const [clientHydrated, setClientHydrated] = useState(false);

  useEffect(() => {
    const id = requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        startTransition(() => setClientHydrated(true));
      });
    });
    return () => cancelAnimationFrame(id);
  }, []);

  const { data, isLoading, refetch } = useQuery({
    queryKey: ["sukoon-theme-public"],
    queryFn: () => fetchPublicTheme(),
    staleTime: 5 * 60 * 1000,
    refetchOnWindowFocus: false,
    retry: 2,
    enabled: clientHydrated,
  });

  const isPublished = data?.published === true && !!data?.tokens;

  const theme = useMemo(() => {
    if (isPublished && data?.tokens) {
      return normalizeThemePayload(data);
    }
    return normalizeThemePayload(DEFAULT_THEME);
  }, [data, isPublished]);

  const apply = useCallback(() => {
    if (!isPublished) {
      setApplied(false);
      clearThemeStyles();
      return;
    }
    applyThemeVariables(theme, { mergeWebSettings: webSettings, active: true });
    setApplied(true);
  }, [theme, webSettings, isPublished]);

  const router = useRouter();

  useEffect(() => {
    if (typeof window !== "undefined") {
      syncHomepagePageMarker(window.location.pathname);
    }
  }, []);

  useEffect(() => {
    if (!router.isReady) return undefined;
    const update = () => syncHomepagePageMarker(router.pathname);
    update();
    router.events.on("routeChangeComplete", update);
    return () => router.events.off("routeChangeComplete", update);
  }, [router.isReady, router.pathname, router.events]);

  useEffect(() => {
    apply();
  }, [apply]);

  useEffect(() => {
    if (webSettings && applied && isPublished) {
      applyThemeVariables(theme, { mergeWebSettings: webSettings, active: true });
      applyHomepageLegacyVarBridge(theme);
    }
  }, [webSettings, theme, applied, isPublished]);

  useEffect(() => {
    if (!isPublished) {
      clearThemeStyles();
    }
  }, [isPublished]);

  const value = useMemo(
    () => ({
      theme,
      isPublished,
      isLoading,
      refreshTheme: () => refetch(),
    }),
    [theme, isPublished, isLoading, refetch]
  );

  return (
    <SukoonThemeContext.Provider value={value}>
      <TrustUiHydrationGate />
      {children}
    </SukoonThemeContext.Provider>
  );
}

export function useSukoonTheme() {
  return useContext(SukoonThemeContext);
}

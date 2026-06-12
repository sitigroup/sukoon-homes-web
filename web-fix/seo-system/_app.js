"use client";
import { TranslationProvider } from "@/components/context/TranslationContext";
import ErrorBoundary from "@/components/error/ErrorBoundary";
import FullScreenSpinLoader from "@/components/ui/loaders/FullScreenSpinLoader";
import { store, persistor } from "@/redux/store";
import { PersistGate } from "redux-persist/integration/react";
import "@/styles/globals.css";
import "@/design-system/tokens/sukoon-tokens.css";
import { Router, useRouter } from "next/router";
import Script from "next/script";
import NProgress from "nprogress";
import "nprogress/nprogress.css";
import { Suspense, useEffect, useState } from "react";
import { Toaster } from "react-hot-toast";
import { Provider } from "react-redux";
import { SukoonThemeProvider } from "@/plugins/theme";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import "react-phone-input-2/lib/style.css";
import { Manrope } from "next/font/google";

const manrope = Manrope({
  subsets: ["latin"],
  display: "swap",
  weight: ["200", "300", "400", "500", "600", "700", "800"]
})

export default function App({ Component, pageProps }) {
  const [queryClient] = useState(() => new QueryClient({
    defaultOptions: {
      queries: {
        staleTime: parseInt(process.env.NEXT_PUBLIC_API_STALE_TIME) || 0,
        refetchOnWindowFocus: false,
        retry: 3,
      },
    }
  })) // 👈 stable client

  // NProgress (SAFE)
  useEffect(() => {
    const start = () => NProgress.start()
    const done = () => NProgress.done()

    Router.events.on("routeChangeStart", start)
    Router.events.on("routeChangeComplete", done)
    Router.events.on("routeChangeError", done)

    return () => {
      Router.events.off("routeChangeStart", start)
      Router.events.off("routeChangeComplete", done)
      Router.events.off("routeChangeError", done)
    }
  }, [])

  // Route errors: ignore cancelled SSR props (race with lang URL sync); reload on chunk failure
  useEffect(() => {
    const onRouteError = (err) => {
      const message = err?.message || ""
      if (
        err?.cancelled ||
        message.includes("Loading initial props cancelled") ||
        message.includes("Cancel rendering route")
      ) {
        NProgress.done()
        return
      }
      if (message.includes("Loading chunk")) {
        NProgress.done()
        window.location.reload()
      }
    }

    Router.events.on("routeChangeError", onRouteError)
    return () => Router.events.off("routeChangeError", onRouteError)
  }, [])

  // Font
  useEffect(() => {
    document.body.classList.add(manrope.className)
  }, [])

  const router = useRouter();
  const [needsPannellum, setNeedsPannellum] = useState(false);

  // Check if the current page needs Pannellum (property or project detail pages)
  useEffect(() => {
    const path = router.asPath;
    const isPannellumNeeded = path.includes('/property-details/') ||
      path.includes('/property-detail-preview/') ||
      path.includes('/project-details/') ||
      path.includes('/my-property/') ||
      path.includes('/my-project/');

    setNeedsPannellum(isPannellumNeeded);
  }, [router.asPath]);

  return (
    <>
      {needsPannellum && (
        <>
          <link
            rel="stylesheet"
            href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css"
          />
          <Script
            src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"
            strategy="afterInteractive"
          />
        </>
      )}
      <ErrorBoundary>
        <QueryClientProvider client={queryClient}>
          <Provider store={store}>
            <PersistGate loading={<FullScreenSpinLoader />} persistor={persistor}>
            <SukoonThemeProvider>
            <TranslationProvider>
              <Suspense fallback={<FullScreenSpinLoader />}>
                <Component {...pageProps} />
              </Suspense>
              <Toaster />
            </TranslationProvider>
                      </SukoonThemeProvider>
            </PersistGate>
          </Provider>
        </QueryClientProvider>
      </ErrorBoundary>
    </>
  );
}

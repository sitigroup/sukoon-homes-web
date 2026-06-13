"use client";

import { useEffect, useLayoutEffect, useState } from "react";
import Head from "next/head";
import Link from "next/link";
import { useRouter } from "next/router";
import MetaData from "@/components/meta/MetaData";
import {
  ShellHeroArea,
  ShellLocationPreviewBlock,
  ShellPreviewFooter,
  ShellPreviewHeader,
  ShellSearchBar,
  SukoonCard,
  SukoonSectionHeader,
  useShellT,
} from "@/design-system";
import { cn } from "@/lib/utils";

function PreviewSection({ title, subtitle, children, className }) {
  return (
    <section className={cn("space-y-sukoon-6", className)}>
      <SukoonSectionHeader title={title} subtitle={subtitle} />
      {children}
    </section>
  );
}

function DeviceFrame({ label, width, children, className }) {
  return (
    <div className={cn("space-y-sukoon-3", className)}>
      <p className="text-sukoon-caption font-semibold uppercase tracking-[0.12em] text-sukoon-graphite-muted">
        {label}
      </p>
      <div
        className="overflow-hidden rounded-sukoon-card border border-dashed border-sukoon-border-strong bg-sukoon-background shadow-sukoon-sm"
        style={{ maxWidth: width }}
      >
        {children}
      </div>
    </div>
  );
}


export default function ShellPreviewPage() {
  const router = useRouter();
  const [lang, setLang] = useState(typeof router.query.lang === "string" ? router.query.lang : "en");
  const t = useShellT(lang);

  useLayoutEffect(() => {
    document.body.classList.add("sukoon-design-preview");
    return () => document.body.classList.remove("sukoon-design-preview");
  }, []);

  useEffect(() => {
    if (typeof router.query.lang === "string") {
      setLang(router.query.lang);
    }
  }, [router.query.lang]);

  const switchLang = (next) => {
    setLang(next);
    router.replace({ pathname: "/shell-preview", query: { lang: next } }, undefined, { shallow: true });
  };

  return (
    <>
      <Head>
        <script
          dangerouslySetInnerHTML={{
            __html: "try{document.body.classList.add('sukoon-design-preview')}catch(e){}",
          }}
        />
      </Head>
      <MetaData title={t("shPageTitle")} />
      <div className="sukoon-ds min-h-screen">
        <header className="border-b border-sukoon-border bg-sukoon-background/95 backdrop-blur-sm">
          <div className="mx-auto flex max-w-6xl flex-col gap-sukoon-4 px-sukoon-4 py-sukoon-8 sm:flex-row sm:items-center sm:justify-between sm:px-sukoon-8">
            <div>
              <p className="text-sukoon-caption font-semibold uppercase tracking-[0.14em] text-sukoon-gold">Sukoon</p>
              <h1 className="mt-sukoon-1 text-sukoon-h2 font-semibold text-sukoon-graphite">{t("shPageTitle")}</h1>
              <p className="mt-sukoon-2 max-w-2xl text-sukoon-body-sm text-sukoon-graphite-muted">{t("shPageSubtitle")}</p>
              <p className="mt-sukoon-2 text-sukoon-caption font-medium text-sukoon-gold">{t("shPreviewOnly")}</p>
            </div>
            <div className="flex flex-wrap items-center gap-sukoon-3">
              <div className="inline-flex rounded-sukoon-input border border-sukoon-border bg-sukoon-page p-0.5">
                {["en", "hi"].map((code) => (
                  <button
                    key={code}
                    type="button"
                    onClick={() => switchLang(code)}
                    className={cn(
                      "rounded-[10px] px-sukoon-4 py-sukoon-2 text-sukoon-caption font-medium transition-colors duration-200",
                      lang === code
                        ? "bg-sukoon-background text-sukoon-graphite shadow-sukoon-sm"
                        : "text-sukoon-graphite-muted hover:text-sukoon-graphite",
                    )}
                  >
                    {code === "en" ? t("shLangEn") : t("shLangHi")}
                  </button>
                ))}
              </div>
              <Link
                href={`/design-preview?lang=${lang}`}
                className="text-sukoon-body-sm font-medium text-sukoon-graphite underline-offset-2 transition-colors duration-200 hover:text-sukoon-gold hover:underline"
              >
                ← Design system
              </Link>
            </div>
          </div>
        </header>

        <main className="mx-auto max-w-6xl space-y-sukoon-16 px-sukoon-4 py-sukoon-12 sm:px-sukoon-8">
          <PreviewSection title={t("shSectionHeaderDesktop")} subtitle={t("shSectionHeaderDesktopSub")}>
            <DeviceFrame label={`${t("shViewportDesktop")} · ${t("shStickyLabel")}`} width="100%">
              <ShellPreviewHeader t={t} lang={lang} onLangChange={switchLang} sticky elevated megaOpen defaultMegaSection="buy" />
              <ShellHeroArea t={t} />
              <div className="min-h-[120px] bg-sukoon-background" />
              <ShellPreviewFooter t={t} variant="desktop" />
            </DeviceFrame>
            <DeviceFrame label={t("shViewportDesktopWide")} width={1440} className="mt-sukoon-8">
              <ShellPreviewHeader t={t} lang={lang} onLangChange={switchLang} sticky elevated />
              <ShellHeroArea t={t} />
              <ShellPreviewFooter t={t} variant="desktop" />
            </DeviceFrame>
          </PreviewSection>

          <PreviewSection title={t("shSectionHeaderMobile")} subtitle={t("shSectionHeaderMobileSub")}>
            <DeviceFrame label={t("shViewportMobile")} width={390}>
              <div className="relative min-h-[720px] overflow-hidden bg-sukoon-page">
                <ShellPreviewHeader
                  t={t}
                  lang={lang}
                  onLangChange={switchLang}
                  variant="mobile"
                  sticky
                  contained
                />
                <ShellHeroArea t={t} />
                <ShellPreviewFooter t={t} variant="mobile" />
              </div>
            </DeviceFrame>
          </PreviewSection>

          <PreviewSection title={t("shSectionSearch")} subtitle={t("shSectionSearchSub")}>
            <div className="grid gap-sukoon-6 md:grid-cols-3">
              {[
                { state: "idle", label: t("shSearchIdle") },
                { state: "listening", label: t("shSearchListening") },
                { state: "result", label: t("shSearchResult") },
              ].map(({ state, label }) => (
                <SukoonCard key={state} variant="subtle" className="p-sukoon-6">
                  <p className="mb-sukoon-4 text-sukoon-caption font-semibold uppercase tracking-[0.1em] text-sukoon-graphite-muted">
                    {label}
                  </p>
                  <ShellSearchBar t={t} demoState={state} interactive={state === "idle"} size="hero" />
                </SukoonCard>
              ))}
            </div>
          </PreviewSection>

          <PreviewSection title={t("shSectionLocation")} subtitle={t("shSectionLocationSub")}>
            <ShellLocationPreviewBlock t={t} />
            <div className="mt-sukoon-8">
              <p className="mb-sukoon-4 text-sukoon-caption font-semibold uppercase tracking-[0.1em] text-sukoon-graphite-muted">
                {t("shViewportMobile")}
              </p>
              <ShellLocationPreviewBlock t={t} mobile />
            </div>
          </PreviewSection>

          <PreviewSection title={t("shSectionFooterDesktop")} subtitle={t("shSectionFooterSub")}>
            <DeviceFrame label={t("shViewportDesktop")} width="100%">
              <ShellPreviewFooter t={t} variant="desktop" />
            </DeviceFrame>
          </PreviewSection>

          <PreviewSection title={t("shSectionFooterMobile")} subtitle={t("shSectionFooterSub")}>
            <DeviceFrame label={t("shViewportMobile")} width={390}>
              <ShellPreviewFooter t={t} variant="mobile" />
            </DeviceFrame>
          </PreviewSection>
        </main>
      </div>
    </>
  );
}

"use client";

import { useEffect, useLayoutEffect, useState } from "react";
import Head from "next/head";
import Link from "next/link";
import { useRouter } from "next/router";

import MetaData from "@/components/meta/MetaData";
import {
  HomepagePreviewContent,
  resolveHomepageSectionVisibility,
  ShellHeroArea,
  ShellPreviewFooter,
  ShellPreviewHeader,
  SukoonButton,
  useHomepageT,
  useShellT,
} from "@/design-system";
import { cn } from "@/lib/utils";

export default function HomepagePreviewPage() {
  const router = useRouter();
  const [lang, setLang] = useState(
    typeof router.query.lang === "string" ? router.query.lang : "en",
  );
  const shellT = useShellT(lang);
  const t = useHomepageT(lang);
  const sectionVisibility = resolveHomepageSectionVisibility();

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
    router.replace({ pathname: "/homepage-preview", query: { lang: next } }, undefined, {
      shallow: true,
    });
  };

  return (
    <>
      <Head>
        <script
          dangerouslySetInnerHTML={{
            __html: `(function(){try{document.documentElement.setAttribute('data-sukoon-preview','true');document.body&&document.body.classList.add('sukoon-design-preview');}catch(e){}})();`,
          }}
        />
      </Head>
      <MetaData title={t("hpPageTitle")} description={t("hpPageSubtitle")} keywords="sukoon,homes,homepage,preview" />
      <div className="sukoon-ds min-h-screen overflow-x-hidden">
        <header className="border-b border-sukoon-border bg-sukoon-background/95 backdrop-blur-sm">
          <div className="mx-auto flex max-w-6xl flex-col gap-sukoon-4 px-sukoon-4 py-sukoon-8 sm:flex-row sm:items-center sm:justify-between sm:px-sukoon-8">
            <div>
              <p className="text-sukoon-caption font-semibold uppercase tracking-[0.14em] text-sukoon-gold">Sukoon</p>
              <h1 className="mt-sukoon-1 text-sukoon-h2 font-semibold text-sukoon-graphite">{t("hpPageTitle")}</h1>
              <p className="mt-sukoon-2 max-w-2xl text-sukoon-body-sm text-sukoon-graphite-muted">{t("hpPageSubtitle")}</p>
              <p className="mt-sukoon-2 text-sukoon-caption font-medium text-sukoon-gold">{t("hpPreviewOnly")}</p>
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
                    {code === "en" ? shellT("shLangEn") : shellT("shLangHi")}
                  </button>
                ))}
              </div>
              <Link
                href={`/shell-preview?lang=${lang}`}
                className="text-sukoon-body-sm font-medium text-sukoon-graphite underline-offset-2 transition-colors duration-200 hover:text-sukoon-gold hover:underline"
              >
                ← Shell preview
              </Link>
            </div>
          </div>
        </header>

        <div className="hidden lg:block">
          <ShellPreviewHeader t={shellT} lang={lang} onLangChange={switchLang} sticky elevated />
        </div>
        <div className="lg:hidden">
          <ShellPreviewHeader
            t={shellT}
            lang={lang}
            onLangChange={switchLang}
            variant="mobile"
            sticky
            elevated
          />
        </div>

        {sectionVisibility.hero ? (
          <div className="relative overflow-hidden border-b border-sukoon-border">
            <div
              className="pointer-events-none absolute inset-0 bg-[linear-gradient(118deg,#e8c4c4_0%,#f0d8d8_45%,#f6e8e8_100%)]"
              aria-hidden
            />
            <div className="relative">
              <ShellHeroArea t={shellT} className="border-b-0 !bg-transparent" />
              <div className="mx-auto w-full max-w-[760px] px-sukoon-4 pb-sukoon-10 text-center sm:px-sukoon-8 sm:pb-sukoon-12">
                <div className="mt-sukoon-5 flex flex-wrap items-center justify-center gap-sukoon-3">
                  <SukoonButton variant="primary" size="lg" onClick={() => {}}>
                    {t("hpExploreProperties")}
                  </SukoonButton>
                  <SukoonButton variant="secondary" size="lg" onClick={() => {}}>
                    {shellT("shGetVerified")}
                  </SukoonButton>
                </div>
              </div>
            </div>
          </div>
        ) : null}

        <HomepagePreviewContent
          t={t}
          shellT={shellT}
          sectionVisibility={sectionVisibility}
          onExplore={() => {}}
          onVerify={() => {}}
        />

        <div className="hidden lg:block">
          <ShellPreviewFooter t={shellT} variant="desktop" />
        </div>
        <div className="lg:hidden">
          <ShellPreviewFooter t={shellT} variant="mobile" />
        </div>
      </div>
    </>
  );
}

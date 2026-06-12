"use client";

import Link from "next/link";
import { useRouter } from "next/router";
import MetaData from "@/components/meta/MetaData";
import NewBreadcrumb from "@/components/breadcrumb/NewBreadCrumb";
import TrustVerificationSiteLayout from "@/plugins/trust-verification/TrustVerificationSiteLayout";
import { resolveVerificationLegalBackHref } from "@/plugins/trust-verification/verificationLegalNavigation";
import {
  TrustVerificationPremiumShell,
  TrustCard,
  VerificationLegalLinks,
  tv,
  cn,
} from "@/plugins/trust-verification/ui";

/**
 * @param {object} props
 * @param {string} props.title
 * @param {string} props.description
 * @param {string} props.pagePath - e.g. /verification-terms
 * @param {Array<{heading?: string, paragraphs?: string[], bullets?: string[]}>} props.sections
 */
export default function VerificationLegalPageLayout({
  title,
  description,
  pagePath,
  sections = [],
}) {
  const router = useRouter();
  const lang = router?.query?.lang || "en";
  const q = lang ? `?lang=${lang}` : "";
  const backHref = resolveVerificationLegalBackHref(router?.query?.return, lang);
  const hasReturn = Boolean(router?.query?.return);

  return (
    <TrustVerificationSiteLayout>
      <MetaData title={`${title} | Sukoon Homes`} description={description} pageName={pagePath} />
      <NewBreadcrumb
        title={title}
        subtitle={description}
        items={[
          { href: `/verification${q}`, label: "Verification" },
          { href: `${pagePath}${q}`, label: title },
        ]}
      />
      <TrustVerificationPremiumShell>
        <div className={tv.section}>
          <p className={tv.badge}>Trust Verification · Legal</p>
          <p className={cn(tv.muted, "mt-4 text-sm")}>Last updated: May 2026 · For Sukoon Homes background verification services</p>

          <div className="mt-8 space-y-4">
            {sections.map((section) => (
              <TrustCard key={section.heading} padding="p-5 sm:p-6">
                {section.heading && (
                  <h2 className={cn(tv.heading, "text-lg")}>{section.heading}</h2>
                )}
                {section.paragraphs?.map((p) => (
                  <p key={p} className={cn(tv.subheading, "mt-3 text-sm leading-relaxed")}>
                    {p}
                  </p>
                ))}
                {section.bullets?.length > 0 && (
                  <ul className={cn(tv.subheading, "mt-3 list-disc space-y-2 pl-5 text-sm leading-relaxed")}>
                    {section.bullets.map((item) => (
                      <li key={item}>{item}</li>
                    ))}
                  </ul>
                )}
              </TrustCard>
            ))}
          </div>

          <div className={cn("mt-10 flex flex-col gap-4 border-t pt-6 sm:flex-row sm:items-center sm:justify-between", tv.divider)}>
            <VerificationLegalLinks lang={lang} returnTo={router?.query?.return} />
            <Link href={backHref} className={tv.btnSecondary}>
              {hasReturn ? "Back to previous page" : "Back to verification"}
            </Link>
          </div>
        </div>
      </TrustVerificationPremiumShell>
    </TrustVerificationSiteLayout>
  );
}

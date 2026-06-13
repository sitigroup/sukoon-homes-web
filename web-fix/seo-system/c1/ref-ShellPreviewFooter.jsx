import { cn } from "@/lib/utils";
import { SukoonFeatureBadge } from "@/design-system";
import SukoonStoreBadges from "../components/SukoonStoreBadges";

const COMPANY_LINKS = ["shFooterAbout", "shFooterContact", "shFooterCareers", "shFooterBlog"];
const TRUST_SERVICES_LINKS = [
  "shTrustVerifiedIdentity",
  "shSvcOwner",
  "shSvcTenant",
  "shFooterAgreements",
  "shSvcPolice",
  "shSvcTrust",
];
const SUPPORT_LINKS = ["shFooterHelp", "shFooterContact", "shFooterFaq"];
const LEGAL_LINKS = ["shFooterPrivacy", "shFooterTerms", "shFooterDataDeletion", "shFooterRefund"];

function FooterColumn({ title, links, t, isMobile }) {
  return (
    <div className="min-w-0">
      <p className="break-words text-[0.6875rem] font-semibold uppercase leading-snug tracking-[0.1em] text-sukoon-graphite-muted">
        {title}
      </p>
      <ul className={cn("mt-sukoon-4", isMobile ? "space-y-sukoon-3" : "space-y-2.5")}>
        {links.map((key) => (
          <li key={key} className="min-w-0">
            <button
              type="button"
              className="text-left text-sukoon-body-sm text-sukoon-graphite-muted transition-colors duration-200 hover:text-sukoon-graphite"
            >
              {t(key)}
            </button>
          </li>
        ))}
      </ul>
    </div>
  );
}

export default function ShellPreviewFooter({ t, variant = "desktop", className }) {
  const isMobile = variant === "mobile";

  return (
    <footer className={cn("overflow-hidden border-t border-sukoon-border bg-sukoon-page text-sukoon-graphite", className)}>
      <div className={cn("mx-auto max-w-6xl", isMobile ? "px-sukoon-4 py-sukoon-8" : "px-sukoon-6 py-sukoon-10 lg:px-sukoon-8 lg:py-sukoon-12")}>
        <div
          className={cn(
            "overflow-hidden rounded-sukoon-card border border-sukoon-border-strong bg-sukoon-background shadow-sukoon-card",
            isMobile ? "px-sukoon-4 py-sukoon-8" : "px-sukoon-6 py-sukoon-10 lg:px-sukoon-8",
          )}
        >
          <div
            className={cn(
              isMobile
                ? "flex flex-col gap-sukoon-10"
                : "grid gap-x-sukoon-5 gap-y-sukoon-10 [grid-template-columns:repeat(2,minmax(0,1fr))] lg:[grid-template-columns:minmax(220px,1.4fr)_repeat(4,minmax(130px,1fr))_minmax(190px,1.1fr)]",
            )}
          >
            <div className="min-w-0">
              <p className="text-sukoon-h3 font-semibold text-sukoon-graphite">{t("shLogo")}</p>
              <p className="mt-sukoon-2 text-sukoon-body-sm font-medium leading-relaxed text-sukoon-graphite-muted">
                {t("shFooterBrandShort")}
              </p>
              <p className="mt-sukoon-4 text-sukoon-body-sm font-medium leading-relaxed text-sukoon-graphite">
                {t("shFooterTagline")}
              </p>
            </div>

            <FooterColumn title={t("shFooterColCompany")} links={COMPANY_LINKS} t={t} isMobile={isMobile} />
            <FooterColumn title={t("shFooterColTrustServices")} links={TRUST_SERVICES_LINKS} t={t} isMobile={isMobile} />
            <FooterColumn title={t("shFooterColSupport")} links={SUPPORT_LINKS} t={t} isMobile={isMobile} />
            <FooterColumn title={t("shFooterColLegal")} links={LEGAL_LINKS} t={t} isMobile={isMobile} />

            <div className={cn("min-w-0", isMobile && "border-t border-sukoon-border pt-sukoon-8")}>
              <p className="break-words text-[0.6875rem] font-semibold uppercase leading-snug tracking-[0.1em] text-sukoon-graphite-muted">
                {t("shFooterContactCol")}
              </p>
              <a
                href={`mailto:${t("shFooterEmail")}`}
                className="mt-sukoon-4 inline-block max-w-full break-all text-sukoon-body-sm font-semibold leading-snug text-sukoon-graphite transition-colors duration-200 hover:text-sukoon-gold"
              >
                {t("shFooterEmail")}
              </a>
              <p className="mt-sukoon-3 break-words text-sukoon-caption leading-relaxed text-sukoon-graphite-subtle">
                {t("shFooterMeta")}
              </p>
            </div>
          </div>

          <div
            className={cn(
              "mt-sukoon-6 flex flex-col gap-sukoon-4 sm:flex-row sm:items-center sm:justify-between",
              isMobile && "items-stretch",
            )}
          >
            <div className="flex flex-wrap gap-sukoon-2">
              <SukoonFeatureBadge variant="verifiedIdentity">{t("shFooterBadgeVerified")}</SukoonFeatureBadge>
              <SukoonFeatureBadge variant="agreementReady">{t("shFooterBadgeAgreement")}</SukoonFeatureBadge>
              <SukoonFeatureBadge variant="policeVerified">{t("shFooterBadgePolice")}</SukoonFeatureBadge>
            </div>
            <SukoonStoreBadges
              layout="row"
              compact
              className={cn("shrink-0", isMobile ? "justify-start" : "justify-end")}
              downloadOnLabel={t("shFooterDownloadOn")}
              playStoreLabel={t("shFooterGooglePlay")}
              appStoreLabel={t("shFooterAppStore")}
            />
          </div>

          <div
            className={cn(
              "mt-sukoon-5 flex flex-col gap-sukoon-2 border-t border-sukoon-border pt-sukoon-5 sm:flex-row sm:items-center sm:justify-between",
              isMobile && "text-center",
            )}
          >
            <p className="text-sukoon-caption text-sukoon-graphite-subtle">{t("shFooterCopyright")}</p>
            <p className="text-sukoon-caption font-medium text-sukoon-graphite-muted">{t("shFooterEntity")}</p>
          </div>
        </div>
      </div>
    </footer>
  );
}

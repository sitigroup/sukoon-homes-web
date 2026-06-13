"use client";

import { useEffect, useState, useLayoutEffect } from "react";
import Head from "next/head";
import { useRouter } from "next/router";
import MetaData from "@/components/meta/MetaData";
import {
  AgreementStatusCard,
  PropertyTrustSummaryCard,
  SukoonAlert,
  SukoonAvatar,
  SukoonBadge,
  SukoonButton,
  SukoonCard,
  SukoonCheckbox,
  SukoonContextSwitcher,
  SukoonDialog,
  SukoonEmptyState,
  SukoonFeatureBadge,
  SukoonInput,
  SukoonListRow,
  SukoonModal,
  SukoonModalBackdrop,
  SukoonProcessTimeline,
  SukoonQuickActionCard,
  SukoonSectionHeader,
  SukoonSelect,
  SukoonSkeleton,
  SukoonSkeletonCard,
  SukoonSkeletonListRow,
  SukoonTable,
  SukoonTabs,
  SukoonTextarea,
  SukoonToast,
  SukoonToggle,
  TrustScoreCard,
  VerificationStatusCard,
  useSukoonDsT,
} from "@/design-system";
import SUKOON_COLOR_TOKENS from "@/design-system/tokens/sukoon-color-tokens";
import {
  MdDescription,
  MdHomeWork,
  MdInfoOutline,
  MdOutlineVerifiedUser,
  MdPayment,
  MdReceipt,
  MdVerifiedUser,
  MdCalendarToday,
  MdShield,
  MdPayments,
  MdSchedule,
} from "react-icons/md";
import { BiErrorCircle } from "react-icons/bi";
import { cn } from "@/lib/utils";

const COLOR_SWATCHES = [
  { labelKey: "dsTokenBg", hex: SUKOON_COLOR_TOKENS.background, bgClass: "bg-sukoon-background" },
  { labelKey: "dsTokenPage", hex: SUKOON_COLOR_TOKENS.page, bgClass: "bg-sukoon-page" },
  { labelKey: "dsTokenGraphite", hex: SUKOON_COLOR_TOKENS.graphite, bgClass: "bg-sukoon-graphite", labelOnDark: true },
  { labelKey: "dsTokenGold", hex: SUKOON_COLOR_TOKENS.gold, bgClass: "bg-sukoon-gold", labelOnDark: true },
  { labelKey: "dsTokenSuccess", hex: SUKOON_COLOR_TOKENS.successBg, bgClass: "bg-sukoon-success-bg" },
  { labelKey: "dsTokenPending", hex: SUKOON_COLOR_TOKENS.pendingBg, bgClass: "bg-sukoon-pending-bg" },
  { labelKey: "dsTokenError", hex: SUKOON_COLOR_TOKENS.errorBg, bgClass: "bg-sukoon-error-bg" },
  { labelKey: "dsTokenInfo", hex: SUKOON_COLOR_TOKENS.infoBg, bgClass: "bg-sukoon-info-bg" },
];

function TokenSwatch({ label, hex, bgClass }) {
  return (
    <div className="flex flex-col gap-sukoon-2">
      <div className={cn("h-16 rounded-sukoon-input border border-sukoon-border-strong shadow-sukoon-sm", bgClass)} />
      <div>
        <p className="text-sukoon-caption font-medium text-sukoon-graphite">{label}</p>
        <p className="text-sukoon-caption text-sukoon-graphite-subtle">{hex}</p>
      </div>
    </div>
  );
}

function PreviewSection({ title, subtitle, children }) {
  return (
    <section className="space-y-sukoon-6">
      <SukoonSectionHeader title={title} subtitle={subtitle} />
      {children}
    </section>
  );
}

function DashboardPanelLabel({ children }) {
  return (
    <h3 className="text-[0.6875rem] font-semibold uppercase tracking-wide text-sukoon-graphite-subtle">{children}</h3>
  );
}

function DashboardSurfaceCard({ title, children, className }) {
  return (
    <div
      className={cn(
        "flex h-full flex-col rounded-sukoon-card border border-sukoon-border-strong bg-sukoon-background p-sukoon-4 shadow-sukoon-card",
        className,
      )}
    >
      {title ? <DashboardPanelLabel>{title}</DashboardPanelLabel> : null}
      {children}
    </div>
  );
}

function DashboardMockupStat({ icon: Icon, label, value }) {
  return (
    <div className="flex flex-col items-center justify-center gap-1 px-sukoon-2 py-2 sm:px-sukoon-3">
      <div className="flex h-7 w-7 items-center justify-center rounded-full bg-sukoon-page text-sukoon-graphite-subtle">
        <Icon className="h-4 w-4" aria-hidden />
      </div>
      <div className="text-[1.375rem] font-semibold leading-none tabular-nums">{value}</div>
      <div className="text-center text-[0.6875rem] font-semibold uppercase tracking-wide text-sukoon-graphite-muted">{label}</div>
    </div>
  );
}

export default function DesignPreviewPage() {
  const router = useRouter();
  const [lang, setLang] = useState(typeof router.query.lang === "string" ? router.query.lang : "en");
  const [tabKey, setTabKey] = useState("overview");
  const [dialogOpen, setDialogOpen] = useState(false);
  const [dashboardContextId, setDashboardContextId] = useState("ctx1");
  const t = useSukoonDsT(lang);

  useLayoutEffect(() => {
    document.body.classList.add("sukoon-design-preview");
    return () => document.body.classList.remove("sukoon-design-preview");
  }, []);

  const switchLang = (next) => {
    setLang(next);
    router.replace({ pathname: "/design-preview", query: { lang: next } }, undefined, { shallow: true });
  };

  const tableColumns = [
    { key: "property", label: t("dsTableColProperty") },
    {
      key: "status",
      label: t("dsTableColStatus"),
      align: "center",
      render: (row) => (
        <SukoonBadge variant={row.status === "active" ? "success" : "pending"}>
          {row.status === "active" ? t("dsAgreementStatus") : t("dsBadgePending")}
        </SukoonBadge>
      ),
    },
    {
      key: "score",
      label: t("dsTableColScore"),
      align: "right",
      render: (row) => <span className="font-semibold tabular-nums text-sukoon-gold">{row.score}</span>,
    },
  ];

  const tableRows = [
    {
      id: 1,
      property: t("dsTableRow1Property"),
      status: "active",
      score: 92,
    },
    {
      id: 2,
      property: t("dsTableRow2Property"),
      status: "pending",
      score: 74,
    },
  ];

  const timelineSteps = [
    { key: "1", label: t("dsTimelineStep1"), status: "completed" },
    { key: "2", label: t("dsTimelineStep2"), status: "completed" },
    { key: "3", label: t("dsTimelineStep3"), status: "completed" },
    { key: "4", label: t("dsTimelineStep4"), status: "completed" },
    { key: "5", label: t("dsTimelineStep5"), status: "active", hint: t("dsTimelineHintActive") },
    { key: "6", label: t("dsTimelineStep6"), status: "future" },
  ];

  const dashboardTimelineSteps = [
    { key: "d1", label: t("dsDashboardTimelineStep1"), status: "completed" },
    { key: "d2", label: t("dsDashboardTimelineStep2"), status: "completed" },
    { key: "d3", label: t("dsDashboardTimelineStep3"), status: "completed" },
    { key: "d4", label: t("dsDashboardTimelineStep4"), status: "completed" },
    { key: "d5", label: t("dsDashboardTimelineStep5"), status: "active" },
    { key: "d6", label: t("dsDashboardTimelineStep6"), status: "future" },
  ];

  const dashboardContexts = [
    { id: "ctx1", name: t("dsDashboardCtx1Name"), meta: t("dsDashboardCtx1Meta"), propertyShort: t("dsDashboardCtx1PropertyShort") },
    { id: "ctx2", name: t("dsDashboardCtx2Name"), meta: t("dsDashboardCtx2Meta"), propertyShort: t("dsDashboardCtx2PropertyShort") },
    { id: "ctx3", name: t("dsDashboardCtx3Name"), meta: t("dsDashboardCtx3Meta"), propertyShort: t("dsDashboardCtx3PropertyShort") },
    { id: "ctx4", name: t("dsDashboardCtx4Name"), meta: t("dsDashboardCtx4Meta"), propertyShort: t("dsDashboardCtx4PropertyShort") },
    { id: "ctx5", name: t("dsDashboardCtx5Name"), meta: t("dsDashboardCtx5Meta"), propertyShort: t("dsDashboardCtx5PropertyShort") },
  ];

  const selectedDashboardContext =
    dashboardContexts.find((context) => context.id === dashboardContextId) ?? dashboardContexts[0];

  const tabs = [
    { key: "overview", label: t("dsTabsOverview"), panel: <p className="text-sukoon-body-sm text-sukoon-graphite-muted">{t("dsTabsPanelOverview")}</p> },
    { key: "verification", label: t("dsTabsVerification"), panel: <p className="text-sukoon-body-sm text-sukoon-graphite-muted">{t("dsTabsPanelVerification")}</p> },
    { key: "agreement", label: t("dsTabsAgreement"), panel: <p className="text-sukoon-body-sm text-sukoon-graphite-muted">{t("dsModalExampleSubtitle")}</p> },
  ];

  return (
    <>
      <Head>
        <script
          dangerouslySetInnerHTML={{
            __html: "try{document.body.classList.add('sukoon-design-preview')}catch(e){}",
          }}
        />
      </Head>
      <MetaData title={t("dsPageTitle")} />
      <div className="sukoon-ds min-h-screen">
        <header className="border-b border-sukoon-border bg-sukoon-background/95 backdrop-blur-sm">
          <div className="mx-auto flex max-w-6xl flex-col gap-sukoon-4 px-sukoon-4 py-sukoon-8 sm:flex-row sm:items-center sm:justify-between sm:px-sukoon-8">
            <div>
              <p className="text-sukoon-caption font-semibold uppercase tracking-[0.14em] text-sukoon-gold">Sukoon</p>
              <h1 className="mt-sukoon-2 text-sukoon-h1 text-sukoon-graphite">{t("dsPageTitle")}</h1>
              <p className="mt-sukoon-2 max-w-2xl text-sukoon-body-sm text-sukoon-graphite-muted">{t("dsPageSubtitle")}</p>
            </div>
            <div className="flex gap-sukoon-2">
              <SukoonButton variant={lang === "en" ? "primary" : "secondary"} size="sm" onClick={() => switchLang("en")}>{t("dsLangEn")}</SukoonButton>
              <SukoonButton variant={lang === "hi" ? "primary" : "secondary"} size="sm" onClick={() => switchLang("hi")}>{t("dsLangHi")}</SukoonButton>
            </div>
          </div>
        </header>

        <main className="mx-auto max-w-6xl space-y-sukoon-12 overflow-x-hidden px-sukoon-4 py-sukoon-10 sm:px-sukoon-8">
          {/* A — Foundations */}
          <PreviewSection title={t("dsFoundationsTitle")} subtitle={t("dsFoundationsSubtitle")}>
            <div className="grid grid-cols-2 gap-sukoon-4 sm:grid-cols-4 lg:grid-cols-8">
              {COLOR_SWATCHES.map((swatch) => (
                <TokenSwatch key={swatch.labelKey} label={t(swatch.labelKey)} hex={swatch.hex} bgClass={swatch.bgClass} />
              ))}
            </div>
          </PreviewSection>

          {/* B — Typography */}
          <PreviewSection title={t("dsTypeTitle")} subtitle={t("dsTypeSubtitle")}>
            <SukoonCard>
              <div className="space-y-sukoon-4">
                <p className="text-sukoon-display text-sukoon-graphite">{t("dsDisplay")}</p>
                <p className="text-sukoon-h1 text-sukoon-graphite">{t("dsH1")}</p>
                <p className="text-sukoon-h2 text-sukoon-graphite">{t("dsH2")}</p>
                <p className="text-sukoon-h3 text-sukoon-graphite">{t("dsH3")}</p>
                <p className="text-sukoon-body text-sukoon-graphite-muted">{t("dsBody")}</p>
                <p className="text-sukoon-caption text-sukoon-graphite-subtle">{t("dsCaption")}</p>
              </div>
            </SukoonCard>
          </PreviewSection>

          {/* C — Buttons */}
          <PreviewSection title={t("dsButtonsTitle")} subtitle={t("dsButtonsSubtitle")}>
            <SukoonCard>
              <div className="space-y-sukoon-8">
                <div>
                  <p className="mb-sukoon-3 text-sukoon-caption text-sukoon-graphite-muted">{t("dsBtnPrimaryHint")}</p>
                  <div className="flex flex-wrap gap-sukoon-3">
                    <SukoonButton variant="primary">{t("dsBtnPrimary")}</SukoonButton>
                    <SukoonButton variant="primary" loading>{t("dsBtnLoading")}</SukoonButton>
                    <SukoonButton variant="primary" disabled>{t("dsBtnDisabled")}</SukoonButton>
                  </div>
                </div>
                <div>
                  <p className="mb-sukoon-3 text-sukoon-caption text-sukoon-graphite-muted">{t("dsBtnSecondaryHint")}</p>
                  <SukoonButton variant="secondary">{t("dsBtnSecondary")}</SukoonButton>
                </div>
                <div>
                  <p className="mb-sukoon-3 text-sukoon-caption text-sukoon-graphite-muted">{t("dsBtnAccentHint")}</p>
                  <SukoonButton variant="accent">{t("dsBtnAccent")}</SukoonButton>
                </div>
                <div className="border-t border-sukoon-border pt-sukoon-6">
                  <p className="text-sukoon-caption font-semibold text-sukoon-graphite">{t("dsGoldUsageTitle")}</p>
                  <p className="mt-sukoon-2 text-sukoon-body-sm text-sukoon-gold">{t("dsGoldUsageList")}</p>
                </div>
              </div>
            </SukoonCard>
          </PreviewSection>

          {/* D — Badges */}
          <PreviewSection title={t("dsBadgesTitle")} subtitle={t("dsBadgesSubtitle")}>
            <SukoonCard>
              <div className="space-y-sukoon-6">
                <div>
                  <p className="mb-sukoon-3 text-sukoon-caption font-semibold text-sukoon-graphite">{t("dsBadgeTierTrust")}</p>
                  <div className="flex flex-wrap gap-sukoon-2">
                    <SukoonFeatureBadge variant="trustedTenant">{t("dsFeatureTrustedTenant")}</SukoonFeatureBadge>
                    <SukoonFeatureBadge variant="trustedOwner">{t("dsFeatureTrustedOwner")}</SukoonFeatureBadge>
                  </div>
                </div>
                <div>
                  <p className="mb-sukoon-3 text-sukoon-caption font-semibold text-sukoon-graphite">{t("dsBadgeTierVerification")}</p>
                  <div className="flex flex-wrap gap-sukoon-2">
                    <SukoonFeatureBadge variant="verifiedIdentity">{t("dsFeatureVerifiedIdentity")}</SukoonFeatureBadge>
                    <SukoonFeatureBadge variant="kycComplete">{t("dsFeatureKycComplete")}</SukoonFeatureBadge>
                    <SukoonFeatureBadge variant="policeVerified">{t("dsFeaturePoliceVerified")}</SukoonFeatureBadge>
                    <SukoonFeatureBadge variant="agreementReady">{t("dsFeatureAgreementReady")}</SukoonFeatureBadge>
                  </div>
                </div>
                <div>
                  <p className="mb-sukoon-3 text-sukoon-caption font-semibold text-sukoon-graphite">{t("dsBadgeTierStatus")}</p>
                  <div className="flex flex-wrap gap-sukoon-2">
                    <SukoonFeatureBadge variant="verificationPending">{t("dsFeatureVerificationPending")}</SukoonFeatureBadge>
                    <SukoonBadge variant="error" icon={BiErrorCircle}>{t("dsBadgeError")}</SukoonBadge>
                    <SukoonBadge variant="paid">{t("dsBadgePaid")}</SukoonBadge>
                    <SukoonBadge variant="info" icon={MdInfoOutline}>{t("dsBadgeInfo")}</SukoonBadge>
                    <SukoonBadge variant="pending" icon={MdSchedule}>{t("dsBadgePending")}</SukoonBadge>
                  </div>
                </div>
              </div>
            </SukoonCard>
          </PreviewSection>

          {/* E — Forms */}
          <PreviewSection title={t("dsFormsTitle")} subtitle={t("dsFormsSubtitle")}>
            <div className="grid gap-sukoon-6 lg:grid-cols-2">
              <SukoonCard>
                <div className="space-y-sukoon-6">
                  <SukoonInput label={t("dsInputLabel")} placeholder={t("dsInputPlaceholder")} hint={t("dsInputHint")} />
                  <SukoonSelect label={t("dsSelectLabel")} hint={t("dsSelectHint")} defaultValue="barmer">
                    <option value="barmer">Barmer</option>
                    <option value="jodhpur">Jodhpur</option>
                  </SukoonSelect>
                  <SukoonTextarea label={t("dsTextareaLabel")} placeholder={t("dsTextareaPlaceholder")} />
                </div>
              </SukoonCard>
              <SukoonCard>
                <div className="space-y-sukoon-6">
                  <SukoonInput label={t("dsInputLabel")} placeholder={t("dsInputPlaceholder")} error={t("dsInputError")} defaultValue="not-an-email" />
                  <SukoonCheckbox label={t("dsCheckboxLabel")} hint={t("dsCheckboxHint")} defaultChecked />
                  <SukoonToggle label={t("dsToggleLabel")} hint={t("dsToggleHint")} defaultChecked />
                  <SukoonTabs tabs={tabs} activeKey={tabKey} onChange={setTabKey} />
                </div>
              </SukoonCard>
            </div>
            <SukoonCard className="mt-sukoon-6">
              <SukoonSectionHeader title={t("dsSkeletonTitle")} subtitle={t("dsSkeletonSubtitle")} />
              <div className="mt-sukoon-6 grid gap-sukoon-6 md:grid-cols-2 lg:grid-cols-4">
                <SukoonSkeletonCard />
                <SukoonSkeletonListRow />
                <SukoonSkeleton variant="stat" />
                <SukoonSkeleton variant="avatar" className="mx-auto" />
              </div>
            </SukoonCard>
          </PreviewSection>

          {/* F — Cards */}
          <PreviewSection title={t("dsCardsTitle")} subtitle={t("dsCardsSubtitle")}>
            <div className="grid gap-sukoon-6 md:grid-cols-2">
              <div>
                <p className="mb-sukoon-3 text-sukoon-caption font-semibold text-sukoon-graphite-muted">{t("dsCardDefaultLabel")}</p>
                <SukoonCard>
                  <h3 className="text-sukoon-h3 text-sukoon-graphite">{t("dsCardTitle")}</h3>
                  <p className="mt-sukoon-3 text-sukoon-body-sm text-sukoon-graphite-muted">{t("dsCardBody")}</p>
                </SukoonCard>
              </div>
              <div>
                <p className="mb-sukoon-3 text-sukoon-caption font-semibold text-sukoon-graphite-muted">{t("dsCardSubtleLabel")}</p>
                <SukoonCard variant="subtle">
                  <h3 className="text-sukoon-h3 text-sukoon-graphite">{t("dsCardTitle")}</h3>
                  <p className="mt-sukoon-3 text-sukoon-body-sm text-sukoon-graphite-muted">{t("dsCardSubtleBody")}</p>
                </SukoonCard>
              </div>
            </div>
            <div className="mt-sukoon-6">
              <p className="mb-sukoon-3 text-sukoon-caption font-semibold text-sukoon-graphite-muted">{t("dsCardNestedLabel")}</p>
              <SukoonCard>
                <h3 className="text-sukoon-h3 text-sukoon-graphite">{t("dsCardDefaultLabel")}</h3>
                <p className="mt-sukoon-2 text-sukoon-body-sm text-sukoon-graphite-muted">{t("dsCardBody")}</p>
                <SukoonCard variant="subtle" padding="sm" className="mt-sukoon-4">
                  <h4 className="text-sukoon-body-sm font-semibold text-sukoon-graphite">{t("dsCardSubtleLabel")}</h4>
                  <p className="mt-sukoon-2 text-sukoon-body-sm text-sukoon-graphite-muted">{t("dsCardNestedBody")}</p>
                </SukoonCard>
              </SukoonCard>
            </div>
          </PreviewSection>

          {/* G — Alerts */}
          <PreviewSection title={t("dsAlertsTitle")} subtitle={t("dsAlertsSubtitle")}>
            <div className="grid gap-sukoon-4 lg:grid-cols-2">
              <SukoonAlert variant="success" title={t("dsAlertSuccessTitle")}>{t("dsAlertSuccessBody")}</SukoonAlert>
              <SukoonAlert variant="warning" title={t("dsAlertWarningTitle")}>{t("dsAlertWarningBody")}</SukoonAlert>
              <SukoonAlert variant="error" title={t("dsAlertErrorTitle")}>{t("dsAlertErrorBody")}</SukoonAlert>
              <SukoonAlert variant="info" title={t("dsAlertInfoTitle")}>{t("dsAlertInfoBody")}</SukoonAlert>
            </div>
          </PreviewSection>

          {/* H — Toasts */}
          <PreviewSection title={t("dsToastsTitle")} subtitle={t("dsToastsSubtitle")}>
            <div className="grid gap-sukoon-4 sm:grid-cols-2">
              <SukoonToast variant="success" title={t("dsToastSuccessTitle")} message={t("dsToastSuccessMsg")} />
              <SukoonToast variant="warning" title={t("dsToastWarningTitle")} meta={t("dsToastWarningMeta")} message={t("dsToastWarningMsg")} />
              <SukoonToast variant="error" title={t("dsToastErrorTitle")} message={t("dsToastErrorMsg")} />
              <SukoonToast variant="info" title={t("dsToastInfoTitle")} message={t("dsToastInfoMsg")} />
            </div>
          </PreviewSection>

          {/* I — Tables */}
          <PreviewSection title={t("dsTablesTitle")} subtitle={t("dsTablesSubtitle")}>
            <SukoonTable
              columns={tableColumns}
              rows={tableRows}
              className="mb-sukoon-6"
              emptyMessage="—"
            />
            <div className="grid gap-sukoon-3">
              <SukoonListRow icon={MdDescription} title={t("dsListRowAgreement")} subtitle={t("dsListRowAgreementSub")} showChevron />
              <SukoonListRow icon={MdVerifiedUser} title={t("dsListRowVerification")} subtitle={t("dsListRowVerificationSub")} meta="92" showChevron />
            </div>
          </PreviewSection>

          {/* J — Modals */}
          <PreviewSection title={t("dsModalsTitle")} subtitle={t("dsModalsSubtitle")}>
            <div className="space-y-sukoon-8">
              <div className="space-y-sukoon-3">
                <p className="text-sukoon-caption font-semibold text-sukoon-graphite-muted">{t("dsModalBackdropLabel")}</p>
                <p className="text-sukoon-caption text-sukoon-graphite-subtle">{t("dsModalBackdropHint")}</p>
                <SukoonModalBackdrop>
                  <div className="w-full max-w-[520px]">
                    <p className="mb-sukoon-3 text-sukoon-caption font-semibold text-sukoon-background">{t("dsModalDialogLabel")}</p>
                    <SukoonModal
                      title={t("dsModalExampleTitle")}
                      subtitle={t("dsModalExampleSubtitle")}
                      footer={<><SukoonButton variant="secondary">{t("dsBtnSecondary")}</SukoonButton><SukoonButton variant="primary">{t("dsBtnPrimary")}</SukoonButton></>}
                    />
                  </div>
                </SukoonModalBackdrop>
              </div>
              <SukoonCard>
                <p className="mb-sukoon-4 text-sukoon-caption text-sukoon-graphite-muted">{t("dsDialogContainedHint")}</p>
                <SukoonButton variant="secondary" onClick={() => setDialogOpen(true)}>{t("dsDialogOpenLabel")}</SukoonButton>
                {dialogOpen ? (
                  <div className="mt-sukoon-6">
                    <SukoonDialog
                      contained
                      open={dialogOpen}
                      onClose={() => setDialogOpen(false)}
                      title={t("dsModalExampleTitle")}
                      subtitle={t("dsModalExampleSubtitle")}
                      footer={
                        <>
                          <SukoonButton variant="secondary" onClick={() => setDialogOpen(false)}>{t("dsBtnSecondary")}</SukoonButton>
                          <SukoonButton variant="primary" onClick={() => setDialogOpen(false)}>{t("dsBtnPrimary")}</SukoonButton>
                        </>
                      }
                    />
                  </div>
                ) : null}
              </SukoonCard>
            </div>
          </PreviewSection>

          {/* K — Trust */}
          <PreviewSection title={t("dsTrustTitle")} subtitle={t("dsTrustSubtitle")}>
            <div className="grid gap-sukoon-6 lg:grid-cols-2">
              <TrustScoreCard
                prominent
                title={t("dsTrustScoreTitle")}
                score={92}
                interpretationLabel={t("dsTrustInterpretation")}
                tierLabel={t("dsTrustTier")}
                signals={[t("dsTrustSignal1"), t("dsTrustSignal2"), t("dsTrustSignal3")]}
              />
              <PropertyTrustSummaryCard
                title={t("dsPropertyTrustTitle")}
                trustScore={92}
                trustScoreLabel={t("dsStatScore")}
                badges={[
                  { variant: "trustedOwner", label: t("dsFeatureTrustedOwner") },
                  { variant: "agreementReady", label: t("dsFeatureAgreementReady") },
                  { variant: "policeVerified", label: t("dsFeaturePoliceVerified") },
                ]}
              />
            </div>
          </PreviewSection>

          {/* L — Verification */}
          <PreviewSection title={t("dsVerificationTitle")} subtitle={t("dsVerificationSubtitle")}>
            <VerificationStatusCard
              title={t("dsVerificationCardTitle")}
              items={[
                { label: t("dsVerificationItem1"), status: "complete" },
                { label: t("dsVerificationItem2"), status: "complete" },
                { label: t("dsVerificationItem3"), status: "info" },
              ]}
              lastUpdated={t("dsVerificationUpdated")}
            />
          </PreviewSection>

          {/* M — Agreement */}
          <PreviewSection title={t("dsAgreementTitle")} subtitle={t("dsAgreementSubtitle")}>
            <AgreementStatusCard
              statusLabel={t("dsAgreementStatus")}
              statusVariant="success"
              remainingLabel={t("dsAgreementRemaining")}
              downloadLabel={t("dsAgreementDownload")}
              viewLabel={t("dsAgreementView")}
            />
          </PreviewSection>

          {/* N — Timeline */}
          <PreviewSection title={t("dsTimelineTitle")} subtitle={t("dsTimelineSubtitle")}>
            <SukoonCard padding="lg" className="max-w-md">
              <SukoonProcessTimeline steps={timelineSteps} />
            </SukoonCard>
          </PreviewSection>

          {/* O — Quick Actions */}
          <PreviewSection title={t("dsQuickActionsTitle")} subtitle={t("dsQuickActionsSubtitle")}>
            <div className="-mx-sukoon-1 flex gap-sukoon-2 overflow-x-auto pb-sukoon-1 sm:mx-0 sm:grid sm:grid-cols-2 sm:overflow-visible sm:pb-0 lg:grid-cols-4">
              <SukoonQuickActionCard compact className="min-w-[152px] shrink-0 sm:min-w-0" icon={MdDescription} title={t("dsQuickViewAgreement")} description={t("dsQuickViewAgreementDesc")} />
              <SukoonQuickActionCard compact className="min-w-[152px] shrink-0 sm:min-w-0" icon={MdPayment} title={t("dsQuickPayRent")} description={t("dsQuickPayRentDesc")} />
              <SukoonQuickActionCard compact className="min-w-[152px] shrink-0 sm:min-w-0" icon={MdReceipt} title={t("dsQuickDownloadReceipt")} description={t("dsQuickDownloadReceiptDesc")} />
              <SukoonQuickActionCard compact className="min-w-[152px] shrink-0 sm:min-w-0" icon={MdVerifiedUser} title={t("dsQuickVerification")} description={t("dsQuickVerificationDesc")} />
            </div>
          </PreviewSection>

          {/* P — Empty States */}
          <PreviewSection title={t("dsEmptySectionTitle")} subtitle={t("dsEmptySectionSubtitle")}>
            <div className="grid gap-sukoon-6 lg:grid-cols-2">
              <SukoonEmptyState icon={MdHomeWork} title={t("dsEmptyTenanciesTitle")} description={t("dsEmptyTenanciesDesc")} action={<SukoonButton variant="primary">{t("dsEmptyTenanciesAction")}</SukoonButton>} />
              <SukoonEmptyState icon={MdDescription} title={t("dsEmptyAgreementsTitle")} description={t("dsEmptyAgreementsDesc")} action={<SukoonButton variant="secondary">{t("dsEmptyAgreementsAction")}</SukoonButton>} />
              <SukoonEmptyState icon={MdHomeWork} title={t("dsEmptyPropertiesTitle")} description={t("dsEmptyPropertiesDesc")} action={<SukoonButton variant="primary">{t("dsEmptyPropertiesAction")}</SukoonButton>} />
              <SukoonEmptyState icon={MdOutlineVerifiedUser} title={t("dsEmptyVerificationTitle")} description={t("dsEmptyVerificationDesc")} action={<SukoonButton variant="primary">{t("dsEmptyVerificationAction")}</SukoonButton>} />
              <SukoonEmptyState icon={MdReceipt} title={t("dsEmptyReceiptsTitle")} description={t("dsEmptyReceiptsDesc")} action={<SukoonButton variant="primary">{t("dsEmptyReceiptsAction")}</SukoonButton>} />
            </div>
          </PreviewSection>

          {/* Stats + Avatars */}
          <PreviewSection title={t("dsStatsTitle")} subtitle={t("dsStatsSubtitle")}>
            <SukoonCard variant="subtle" padding="sm" className="py-sukoon-2">
              <div className="grid grid-cols-3 divide-x divide-sukoon-border">
                <DashboardMockupStat icon={MdPayments} label={t("dsStatRent")} value="₹18,500" />
                <DashboardMockupStat icon={MdCalendarToday} label={t("dsStatDays")} value="124" />
                <DashboardMockupStat icon={MdShield} label={t("dsStatScore")} value={<span className="text-sukoon-gold">92</span>} />
              </div>
            </SukoonCard>
          </PreviewSection>

          <PreviewSection title={t("dsAvatarsTitle")} subtitle={t("dsAvatarsSubtitle")}>
            <SukoonCard>
              <div className="flex flex-wrap items-end gap-sukoon-6">
                <SukoonAvatar initials="SK" size="sm" />
                <SukoonAvatar initials="SU" size="md" />
                <SukoonAvatar initials="KO" size="lg" goldRing />
                <SukoonAvatar initials="ON" size="xl" />
              </div>
            </SukoonCard>
          </PreviewSection>

          {/* Q — Mobile */}
          <PreviewSection title={t("dsMobileTitle")} subtitle={t("dsMobileSubtitle")}>
            <div className="mx-auto max-w-[390px] space-y-sukoon-4 overflow-hidden rounded-sukoon-card border border-dashed border-sukoon-border bg-sukoon-page/50 p-sukoon-4">
              <TrustScoreCard
                prominent
                score={92}
                interpretationLabel={t("dsTrustInterpretation")}
                tierLabel={t("dsTrustTier")}
                signals={[t("dsTrustSignal1"), t("dsTrustSignal2")]}
                title={t("dsTrustScoreTitle")}
              />
              <div className="grid grid-cols-2 gap-sukoon-2">
                <SukoonQuickActionCard dashboard icon={MdPayment} title={t("dsQuickPayRent")} />
                <SukoonQuickActionCard dashboard icon={MdVerifiedUser} title={t("dsQuickVerification")} />
              </div>
              <SukoonListRow icon={MdDescription} title={t("dsListRowAgreement")} subtitle={t("dsListRowAgreementSub")} showChevron />
            </div>
          </PreviewSection>

          {/* R — Dashboard Mockup */}
          <PreviewSection title={t("dsDashboardTitle")} subtitle={t("dsDashboardSubtitle")}>
            <SukoonCard padding="md" variant="elevated" className="space-y-sukoon-4">
              <div className="border-b border-sukoon-border pb-sukoon-4">
                <div className="flex flex-col gap-sukoon-3 sm:flex-row sm:items-end sm:justify-between">
                  <div className="min-w-0">
                    <h3 className="text-sukoon-h2 text-sukoon-graphite">{t("dsDashboardGreeting")}</h3>
                    <p className="mt-sukoon-1 text-sukoon-body-sm text-sukoon-graphite-muted">{t("dsDashboardSubline")}</p>
                  </div>
                  <div className="flex shrink-0 flex-wrap items-center gap-sukoon-2 sm:justify-end">
                    <SukoonFeatureBadge variant="trustedTenant">{t("dsFeatureTrustedTenant")}</SukoonFeatureBadge>
                    <SukoonFeatureBadge variant="kycComplete">{t("dsFeatureKycComplete")}</SukoonFeatureBadge>
                  </div>
                </div>
              </div>

              <SukoonContextSwitcher
                title={t("dsDashboardContextTitle")}
                switchLabel={t("dsDashboardContextSwitchLabel")}
                options={dashboardContexts}
                selectedId={dashboardContextId}
                onSelect={setDashboardContextId}
              />

              <div className="grid gap-sukoon-3 lg:grid-cols-12 lg:grid-rows-2">
                <div className="min-w-0 lg:col-span-5 lg:row-span-2">
                  <TrustScoreCard
                    prominent
                    heroEmphasis
                    fillHeight
                    score={92}
                    interpretationLabel={t("dsTrustInterpretation")}
                    tierLabel={t("dsTrustTier")}
                    signals={[t("dsTrustSignal1"), t("dsTrustSignal2"), t("dsTrustSignal3")]}
                    title={t("dsTrustScoreTitle")}
                  />
                </div>
                <div className="min-w-0 lg:col-span-7">
                  <VerificationStatusCard
                    dashboard
                    title={t("dsVerificationCardTitle")}
                    progressLabel={t("dsDashboardVerificationProgress")}
                    statusPill={t("dsDashboardVerificationStatus")}
                    progressPercent={100}
                    items={[
                      { label: t("dsVerificationItem1"), status: "complete" },
                      { label: t("dsVerificationItem2"), status: "complete" },
                      { label: t("dsVerificationItem3"), status: "info" },
                    ]}
                    lastUpdated={t("dsVerificationUpdated")}
                  />
                </div>
                <div className="min-w-0 lg:col-span-7">
                  <AgreementStatusCard
                    dashboard
                    emphasis
                    cardTitle={t("dsDashboardAgreementTitle")}
                    propertyHint={selectedDashboardContext.propertyShort}
                    statusLabel={t("dsAgreementStatus")}
                    remainingHero={t("dsAgreementRemainingHero")}
                    remainingCaption={t("dsAgreementRemainingCaption")}
                    downloadLabel={t("dsAgreementDownload")}
                    viewLabel={t("dsAgreementView")}
                  />
                </div>
              </div>

              <DashboardSurfaceCard title={t("dsQuickActionsTitle")} className="!h-auto">
                <div className="mt-sukoon-3 grid grid-cols-2 gap-sukoon-2 lg:grid-cols-4">
                  <SukoonQuickActionCard dashboard className="h-full" icon={MdDescription} title={t("dsQuickViewAgreement")} />
                  <SukoonQuickActionCard dashboard className="h-full" icon={MdPayment} title={t("dsQuickPayRent")} />
                  <SukoonQuickActionCard dashboard className="h-full" icon={MdReceipt} title={t("dsQuickDownloadReceipt")} />
                  <SukoonQuickActionCard dashboard className="h-full" icon={MdVerifiedUser} title={t("dsQuickVerification")} />
                </div>
              </DashboardSurfaceCard>

              <div className="grid gap-sukoon-3 lg:grid-cols-12 lg:items-stretch">
                <div className="min-w-0 lg:col-span-8">
                  <DashboardSurfaceCard title={t("dsStatsTitle")} className="min-h-[9.5rem]">
                    <div className="mt-sukoon-3 flex flex-1 items-center">
                      <div className="grid w-full grid-cols-3 divide-x divide-sukoon-border">
                        <DashboardMockupStat icon={MdPayments} label={t("dsStatRent")} value="₹18,500" />
                        <DashboardMockupStat icon={MdCalendarToday} label={t("dsStatDays")} value="124" />
                        <DashboardMockupStat icon={MdShield} label={t("dsStatScore")} value={<span className="text-sukoon-gold">92</span>} />
                      </div>
                    </div>
                  </DashboardSurfaceCard>
                </div>
                <div className="min-w-0 lg:col-span-4">
                  <DashboardSurfaceCard title={t("dsTimelineTitle")} className="min-h-[9.5rem]">
                    <p className="mt-sukoon-2 text-sukoon-body-sm text-sukoon-graphite-muted">{t("dsDashboardTimelineStatus")}</p>
                    <div className="mt-sukoon-3 flex flex-1 items-center">
                      <SukoonProcessTimeline dashboard steps={dashboardTimelineSteps} className="w-full" />
                    </div>
                  </DashboardSurfaceCard>
                </div>
              </div>
            </SukoonCard>
          </PreviewSection>
        </main>

        <footer className="border-t border-sukoon-border py-sukoon-8 text-center text-sukoon-caption text-sukoon-graphite-subtle">
          Sukoon Design System — preview only
        </footer>
      </div>
    </>
  );
}

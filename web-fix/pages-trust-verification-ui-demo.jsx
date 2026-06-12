"use client";

import MetaData from "@/components/meta/MetaData";
import TrustVerificationSiteLayout from "@/plugins/trust-verification/TrustVerificationSiteLayout";
import {
  TrustVerificationPremiumShell,
  TrustCard,
  VerificationPackageCard,
  VerificationTimeline,
  TrustScoreCard,
  VerificationStatusBadge,
  VerificationProgressStepper,
  VerificationEmptyState,
  VerificationSuccessScreen,
  VerificationHubHero,
  VerificationTrustBadges,
  VerificationFaq,
  VerificationConsentCheckbox,
  VerificationDataUsageNotice,
  VerificationReportDisclaimer,
  VerificationLegalLinks,
  buildVerificationTimelineSimple,
  tv,
} from "@/plugins/trust-verification/ui";

const MOCK_PACKAGES = [
  {
    id: 1,
    name: "Basic",
    price: "₹499",
    deliveryHours: 72,
    features: [
      { key: "id", label: "ID verification", included: true },
      { key: "addr", label: "Address check", included: false },
    ],
  },
  {
    id: 2,
    name: "Standard",
    price: "₹999",
    deliveryHours: 48,
    recommended: true,
    features: [
      { key: "id", label: "ID verification", included: true },
      { key: "addr", label: "Address check", included: true },
      { key: "emp", label: "Employment", included: true },
    ],
  },
];

const MOCK_ORDER = {
  status: "in_progress",
  payment_status: "paid",
  report: {},
};

export default function TrustVerificationUiDemoPage() {
  const timeline = buildVerificationTimelineSimple(MOCK_ORDER);

  return (
    <TrustVerificationSiteLayout>
      <MetaData
        title="Trust Verification UI Demo | Sukoon Homes"
        description="Component preview — not for production use."
        pageName="/trust-verification-ui-demo"
      />
      <TrustVerificationPremiumShell>
        <div className={tv.section}>
          <VerificationHubHero
            title="UI component preview"
            subtitle="Premium Trust Verification design system — review before production routes."
          />

          <p className="mb-8 text-xs text-[#8A8A8A]">
            Route: /trust-verification-ui-demo · UI only · no API calls
          </p>

          <section className="mb-10">
            <h2 className="mb-4 text-lg font-semibold text-[#D4AF37]">Status badges</h2>
            <div className="flex flex-wrap gap-2">
              <VerificationStatusBadge label="Submitted" variant="submitted" />
              <VerificationStatusBadge label="In progress" variant="in_progress" />
              <VerificationStatusBadge label="Paid" variant="paid" />
              <VerificationStatusBadge label="Pending" variant="pending" />
            </div>
          </section>

          <section className="mb-10">
            <h2 className="mb-4 text-lg font-semibold text-[#D4AF37]">Package cards</h2>
            <div className="grid gap-4 md:grid-cols-2">
              {MOCK_PACKAGES.map((pkg, i) => (
                <VerificationPackageCard
                  key={pkg.id}
                  name={pkg.name}
                  price={pkg.price}
                  deliveryHours={pkg.deliveryHours}
                  features={pkg.features}
                  selected={i === 1}
                  recommended={pkg.recommended}
                  onSelect={() => {}}
                />
              ))}
            </div>
          </section>

          <section className="mb-10 grid gap-6 lg:grid-cols-2">
            <div>
              <h2 className="mb-4 text-lg font-semibold text-[#D4AF37]">Progress stepper</h2>
              <TrustCard>
                <VerificationProgressStepper currentStep={3} totalSteps={5} />
              </TrustCard>
            </div>
            <div>
              <h2 className="mb-4 text-lg font-semibold text-[#D4AF37]">Trust score</h2>
              <TrustScoreCard riskLevel="green" summary="Based on submitted documents and checks." />
            </div>
          </section>

          <section className="mb-10 grid gap-6 lg:grid-cols-2">
            <VerificationTimeline steps={timeline} />
            <div className="space-y-6">
              <VerificationEmptyState
                title="No orders"
                description="Start a verification from the hub."
                action={<span className={tv.btnPrimary}>Browse packages</span>}
              />
              <VerificationSuccessScreen
                title="Payment confirmed"
                orderNumber="TV-2026-00042"
                message="Your report will be emailed within 48 hours."
                primaryHref="/my-verification-orders"
              />
            </div>
          </section>

          <section className="mb-10">
            <h2 className="mb-4 text-lg font-semibold text-[#D4AF37]">Legal & consent</h2>
            <div className="grid gap-4 lg:grid-cols-2">
              <TrustCard>
                <VerificationConsentCheckbox checked onChange={() => {}} />
              </TrustCard>
              <TrustCard>
                <VerificationDataUsageNotice />
                <VerificationReportDisclaimer className="mt-4" />
              </TrustCard>
            </div>
            <VerificationLegalLinks className="mt-4" />
          </section>

          <section className="mb-10">
            <VerificationTrustBadges className="mb-6" />
            <VerificationFaq />
          </section>
        </div>
      </TrustVerificationPremiumShell>
    </TrustVerificationSiteLayout>
  );
}

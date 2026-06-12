"use client";

import { useRouter } from "next/router";
import { useEffect, useState } from "react";
import MetaData from "@/components/meta/MetaData";
import { getTrustVerificationCities } from "@/plugins/trust-verification/trustVerificationApi";
import TrustVerificationSiteLayout from "@/plugins/trust-verification/TrustVerificationSiteLayout";
import {
  TrustVerificationPremiumShell,
  VerificationHubPremium,
} from "@/plugins/trust-verification/ui";

export default function VerificationHubPage() {
  const router = useRouter();
  const lang = router?.query?.lang || "en";
  const [cities, setCities] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getTrustVerificationCities()
      .then((res) => setCities(res?.data || []))
      .catch(() => setCities([]))
      .finally(() => setLoading(false));
  }, []);

  return (
    <TrustVerificationSiteLayout>
      <MetaData
        title="Background verification for rentals | Sukoon Homes"
        description="Verify tenants or property owners before you rent — ID checks and PDF reports by city."
        pageName="/verification"
      />

      <TrustVerificationPremiumShell>
        <VerificationHubPremium cities={cities} loading={loading} lang={lang} />
      </TrustVerificationPremiumShell>
    </TrustVerificationSiteLayout>
  );
}

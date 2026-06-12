"use client";

import { startTransition, useEffect, useState } from "react";
import SukoonTenantReliabilityCard from "./SukoonTenantReliabilityCard";
import { sanitizeTenantReliability } from "./sanitizeTenantReliability";
import { fetchTenantReliabilityCached } from "./tenantReliabilityCache";
import { useClientTrustReady } from "./trustUiHydration";
import styles from "./trustVerificationPremium.module.css";

/**
 * Owner-facing tenant reliability (property inquiry, screening, dashboard).
 * Pass tenant applicant customerId — not property owner added_by.
 */
export default function SukoonTenantReliabilitySection({ customerId, className = "" }) {
  const ready = useClientTrustReady();
  const [reliability, setReliability] = useState(null);

  useEffect(() => {
    if (!ready || !customerId) return;
    let cancelled = false;
    fetchTenantReliabilityCached(customerId)
      .then((data) => {
        if (!cancelled && data) {
          startTransition(() => setReliability(sanitizeTenantReliability(data)));
        }
      })
      .catch(() => {});
    return () => {
      cancelled = true;
    };
  }, [ready, customerId]);

  if (!ready || !reliability) {
    return null;
  }

  return (
    <div
      className={`${styles.tenantReliabilitySection || ""} w-full ${className}`.trim()}
      data-sukoon-tenant-reliability-section
    >
      <SukoonTenantReliabilityCard reliability={reliability} />
    </div>
  );
}

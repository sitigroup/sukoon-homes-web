"use client";

import { startTransition, useEffect, useState } from "react";
import { FiCheck } from "react-icons/fi";
import { sanitizePublicTrust } from "./sanitizePublicTrust";
import { fetchPublicTrustCached } from "./trustProfileCache";
import { useClientTrustReady } from "./trustUiHydration";
import { resolvePropertyCardTrustLabel } from "./propertyCardTrustUtils";
import styles from "./trustVerificationPremium.module.css";

export default function PropertyCardTrustLabel({ customerId }) {
  const ready = useClientTrustReady();
  const [label, setLabel] = useState(null);

  useEffect(() => {
    if (!ready || !customerId) return;
    let cancelled = false;

    fetchPublicTrustCached(customerId)
      .then((data) => {
        if (cancelled) return;
        const resolved = resolvePropertyCardTrustLabel(sanitizePublicTrust(data));
        startTransition(() => setLabel(resolved));
      })
      .catch(() => {
        if (!cancelled) {
          startTransition(() => setLabel(null));
        }
      });

    return () => {
      cancelled = true;
    };
  }, [ready, customerId]);

  if (!label) {
    return null;
  }

  return (
    <span
      className={styles.propertyCardTrustLabel || ""}
      data-property-card-trust="owner"
      aria-label={label}
    >
      <FiCheck className={styles.propertyCardTrustIcon || ""} aria-hidden />
      {label}
    </span>
  );
}

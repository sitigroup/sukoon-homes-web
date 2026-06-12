"use client";

import { startTransition, useEffect, useState } from "react";
import SukoonTrustCard from "./SukoonTrustCard";
import { sanitizePublicTrust } from "./sanitizePublicTrust";
import { fetchPublicTrustCached } from "./trustProfileCache";
import { useClientTrustReady } from "./trustUiHydration";
import styles from "./trustVerificationPremium.module.css";

export default function SukoonPublicTrustProfile({ customerId, className = "" }) {
  const ready = useClientTrustReady();
  const [trust, setTrust] = useState(null);

  useEffect(() => {
    if (!ready || !customerId) return;
    let cancelled = false;
    fetchPublicTrustCached(customerId)
      .then((data) => {
        if (!cancelled && data) {
          startTransition(() => setTrust(sanitizePublicTrust(data)));
        }
      })
      .catch(() => {});
    return () => {
      cancelled = true;
    };
  }, [ready, customerId]);

  if (!ready || !trust || trust.trust_score == null) {
    return null;
  }

  return (
    <div className={`${styles.publicTrustWrap || ""} ${className}`.trim()} data-sukoon-public-trust>
      <SukoonTrustCard trust={trust} />
    </div>
  );
}

"use client";

import { startTransition, useEffect, useState } from "react";
import { getPublicTrustProfile } from "../trustVerificationApi";
import SukoonTrustCard from "./SukoonTrustCard";
import { sanitizePublicTrust } from "./sanitizePublicTrust";
import { fetchPublicTrustCached } from "./trustProfileCache";
import { useClientTrustReady } from "./trustUiHydration";
import styles from "./trustVerificationPremium.module.css";

/**
 * Property detail owner trust (client-only parent). Score, tier, badges — no breakdown.
 */
export default function SukoonOwnerTrustSection({ customerId }) {
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
    <div className={`${styles.ownerTrustSection || ""} w-full`} data-sukoon-owner-trust>
      <SukoonTrustCard trust={trust} title="Sukoon Trust Score" />
    </div>
  );
}

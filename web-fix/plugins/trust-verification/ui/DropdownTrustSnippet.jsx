"use client";

import { startTransition, useEffect, useState } from "react";
import { getTrustVerificationTrustProfile } from "../trustVerificationApi";
import SukoonTrustRing from "./SukoonTrustRing";
import SukoonTrustTierPill from "./SukoonTrustTierPill";
import { sanitizePublicTrust } from "./sanitizePublicTrust";
import { clampTrustScore } from "./trustTierUtils";
import { useClientTrustReady } from "./trustUiHydration";
import styles from "./trustVerificationPremium.module.css";

export default function DropdownTrustSnippet() {
  const ready = useClientTrustReady();
  const [trust, setTrust] = useState(null);

  useEffect(() => {
    if (!ready) return;
    let cancelled = false;
    getTrustVerificationTrustProfile()
      .then((data) => {
        if (!cancelled && data?.trust_score != null) {
          startTransition(() => setTrust(sanitizePublicTrust(data)));
        }
      })
      .catch(() => {});
    return () => {
      cancelled = true;
    };
  }, [ready]);

  if (!ready || !trust || trust.trust_score == null) {
    return null;
  }

  const score = clampTrustScore(trust.trust_score);

  return (
    <div className={styles.dropdownTrustSnippet || ""} aria-label={`Sukoon Trust ${score}`}>
      <SukoonTrustRing score={score} size="sm" />
      <div className={styles.dropdownTrustSnippetMeta || ""}>
        <span className={styles.dropdownTrustSnippetLabel || ""}>Sukoon Trust</span>
        <SukoonTrustTierPill score={score} />
      </div>
    </div>
  );
}

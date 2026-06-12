"use client";

import { startTransition, useEffect, useState } from "react";
import { getTrustVerificationTrustProfile } from "./trustVerificationApi";
import SukoonTrustCard from "./ui/SukoonTrustCard";
import VerificationIssuedBadgesList from "./ui/VerificationIssuedBadgesList";
import { sanitizePublicTrust } from "./ui/sanitizePublicTrust";
import { useClientTrustReady } from "./ui/trustUiHydration";
import styles from "./ui/trustVerificationPremium.module.css";

export default function TrustProfileSection({ compact = false, className = "" }) {
  const ready = useClientTrustReady();
  const [trust, setTrust] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!ready) return;
    let cancelled = false;
    getTrustVerificationTrustProfile()
      .then((data) => {
        if (!cancelled) {
          startTransition(() => setTrust(sanitizePublicTrust(data)));
        }
      })
      .catch(() => {})
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [ready]);

  const sectionClass = `${styles.trustProfileSection || ""} ${className}`.trim();

  if (!ready || loading) {
    return (
      <section className={sectionClass}>
        <p className="leadColor text-sm mb-0">Loading trust profile…</p>
      </section>
    );
  }

  if (!trust) {
    if (compact) return null;
    return (
      <section className={sectionClass}>
        <h3 className="text-base font-bold brandColor">Sukoon Trust</h3>
        <p className="leadColor text-sm mb-0 mt-2">
          Complete a verification order to earn your trust score and badges.
        </p>
      </section>
    );
  }

  return (
    <section className={sectionClass}>
      {!compact ? <h3 className="text-base font-bold brandColor mb-3">Sukoon Trust</h3> : null}
      <SukoonTrustCard trust={trust} compact={compact} title="Sukoon Trust Score" />
      <VerificationIssuedBadgesList badges={trust.verification_badges} />
    </section>
  );
}

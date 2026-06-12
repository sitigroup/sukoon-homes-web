"use client";

import { getTrustTier, getTrustTierClassName } from "./trustTierUtils";
import styles from "./trustVerificationPremium.module.css";

export default function SukoonTrustTierPill({ score, className = "" }) {
  const tier = getTrustTier(score);
  const tierClass = styles[getTrustTierClassName(tier.id)] || "";

  return (
    <span
      className={`${styles.trustTierPill || ""} ${tierClass} ${className}`.trim()}
      style={{ color: tier.color, backgroundColor: tier.surface, borderColor: tier.color }}
    >
      {tier.label}
    </span>
  );
}

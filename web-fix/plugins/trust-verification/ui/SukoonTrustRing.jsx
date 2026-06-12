"use client";

import { clampTrustScore, getTrustTier, getTrustTierClassName } from "./trustTierUtils";
import styles from "./trustVerificationPremium.module.css";

const SIZES = {
  sm: { box: 44, stroke: 4, font: "0.875rem", sub: "0.5625rem" },
  md: { box: 96, stroke: 6, font: "1.375rem", sub: "0.6875rem" },
  lg: { box: 120, stroke: 7, font: "1.75rem", sub: "0.75rem" },
};

/**
 * Circular trust score ring (score / 100).
 */
export default function SukoonTrustRing({ score, size = "md", className = "" }) {
  const value = clampTrustScore(score);
  const tier = getTrustTier(value);
  const dim = SIZES[size] || SIZES.md;
  const radius = (dim.box - dim.stroke) / 2;
  const circumference = 2 * Math.PI * radius;
  const progress = (value / 100) * circumference;
  const offset = circumference - progress;
  const center = dim.box / 2;
  const tierClass = styles[getTrustTierClassName(tier.id)] || "";

  return (
    <div
      className={`${styles.trustRingWrap || ""} ${tierClass} ${className}`.trim()}
      style={{ width: dim.box, height: dim.box }}
      role="img"
      aria-label={`Sukoon Trust Score ${value} out of 100, ${tier.label} tier`}
    >
      <svg width={dim.box} height={dim.box} className={styles.trustRingSvg || ""}>
        <circle
          className={styles.trustRingTrack || ""}
          cx={center}
          cy={center}
          r={radius}
          strokeWidth={dim.stroke}
          style={{ stroke: tier.ringTrack }}
        />
        <circle
          className={styles.trustRingProgress || ""}
          cx={center}
          cy={center}
          r={radius}
          strokeWidth={dim.stroke}
          strokeDasharray={circumference}
          strokeDashoffset={offset}
          style={{ stroke: tier.color }}
        />
      </svg>
      <div className={styles.trustRingCenter || ""}>
        <span className={styles.trustRingScore || ""} style={{ fontSize: dim.font, color: tier.color }}>
          {value}
        </span>
        <span className={styles.trustRingMax || ""} style={{ fontSize: dim.sub }}>
          / 100
        </span>
      </div>
    </div>
  );
}

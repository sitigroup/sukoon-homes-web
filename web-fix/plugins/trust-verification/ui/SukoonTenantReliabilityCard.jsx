"use client";

import { useId, useState } from "react";
import styles from "./trustVerificationPremium.module.css";
import {
  getPositiveReliabilityFactors,
  getReliabilityBadge,
  hasReliabilityData,
  RELIABILITY_PRIVACY_EXCLUDED,
  RELIABILITY_TOOLTIP_TEXT,
} from "./tenantReliabilityUx";

const BADGE_CLASS = {
  high: "tenantReliabilityBadgeHigh",
  medium: "tenantReliabilityBadgeMedium",
  pending: "tenantReliabilityBadgePending",
};

/**
 * Owner-safe tenant reliability — explainability without scores, PII, or risk.
 */
export default function SukoonTenantReliabilityCard({ reliability, className = "" }) {
  const tooltipId = useId();
  const [tooltipOpen, setTooltipOpen] = useState(false);

  if (!hasReliabilityData(reliability)) {
    return null;
  }

  const badge = getReliabilityBadge(reliability);
  const factors = getPositiveReliabilityFactors(reliability);
  const badgeClass = styles[BADGE_CLASS[badge.tone]] || "";

  return (
    <div
      className={`${styles.tenantReliabilityCard || ""} ${className}`.trim()}
      data-sukoon-tenant-reliability
    >
      <div className={styles.tenantReliabilityAccent || ""} aria-hidden="true" />

      <div className={styles.tenantReliabilityHeader || ""}>
        <div className={styles.tenantReliabilityHeaderMain || ""}>
          <span className={styles.tenantReliabilityTitle || ""}>Tenant Reliability</span>
          <span className={`${styles.tenantReliabilityBadge || ""} ${badgeClass}`.trim()}>
            {badge.label}
          </span>
        </div>

        <div className={styles.tenantReliabilityTooltipWrap || ""}>
          <button
            type="button"
            className={styles.tenantReliabilityTooltipTrigger || ""}
            aria-expanded={tooltipOpen}
            aria-describedby={tooltipOpen ? tooltipId : undefined}
            onClick={() => setTooltipOpen((open) => !open)}
            onBlur={() => setTooltipOpen(false)}
          >
            What does this mean?
          </button>
          {tooltipOpen ? (
            <p id={tooltipId} role="tooltip" className={styles.tenantReliabilityTooltip || ""}>
              {RELIABILITY_TOOLTIP_TEXT}
            </p>
          ) : null}
        </div>
      </div>

      {factors.length > 0 ? (
        <section className={styles.tenantReliabilityBlock || ""}>
          <h4 className={styles.tenantReliabilityBlockTitle || ""}>Reliability Factors</h4>
          <ul className={styles.tenantReliabilityFactors || ""}>
            {factors.map((factor) => (
              <li key={factor} className={styles.tenantReliabilityFactorItem || ""}>
                <span className={styles.tenantReliabilityFactorIcon || ""} aria-hidden="true">
                  ✓
                </span>
                <span>{factor}</span>
              </li>
            ))}
          </ul>
        </section>
      ) : null}

      <section className={styles.tenantReliabilityBlock || ""}>
        <h4 className={styles.tenantReliabilityBlockTitle || ""}>Not considered</h4>
        <ul className={styles.tenantReliabilityPrivacyList || ""}>
          {RELIABILITY_PRIVACY_EXCLUDED.map((item) => (
            <li key={item} className={styles.tenantReliabilityPrivacyItem || ""}>
              <span className={styles.tenantReliabilityPrivacyIcon || ""} aria-hidden="true">
                ✗
              </span>
              <span>{item}</span>
            </li>
          ))}
        </ul>
      </section>
    </div>
  );
}

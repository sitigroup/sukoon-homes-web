"use client";

import SukoonTrustBadges from "./SukoonTrustBadges";
import SukoonTrustRing from "./SukoonTrustRing";
import SukoonTrustTierPill from "./SukoonTrustTierPill";
import { sanitizePublicTrust } from "./sanitizePublicTrust";
import { clampTrustScore } from "./trustTierUtils";
import styles from "./trustVerificationPremium.module.css";

/**
 * Public trust panel: score ring, tier, verification badges only.
 * Never renders score breakdown / point weights (admin-only).
 */
export default function SukoonTrustCard({
  trust,
  compact = false,
  title = "Sukoon Trust Score",
}) {
  const publicTrust = sanitizePublicTrust(trust);

  if (!publicTrust || publicTrust.trust_score == null) {
    return null;
  }

  const score = clampTrustScore(publicTrust.trust_score);

  if (compact) {
    return (
      <div
        className={`${styles.trustCard || ""} ${styles.trustCardCompact || ""}`.trim()}
        data-sukoon-trust-card
      >
        <div className={styles.trustCardCompactRow || ""}>
          <SukoonTrustRing score={score} size="sm" />
          <div className={styles.trustCardCompactMeta || ""}>
            <span className={styles.trustCardLabel || ""}>{title}</span>
            <SukoonTrustTierPill score={score} />
          </div>
        </div>
        <SukoonTrustBadges badges={publicTrust.badges} max={4} variant="chip" />
      </div>
    );
  }

  return (
    <div className={styles.trustCard || ""} data-sukoon-trust-card>
      <div className={styles.trustCardHero || ""}>
        <SukoonTrustRing score={score} size="lg" />
        <div className={styles.trustCardHeroText || ""}>
          <span className={styles.trustCardLabel || ""}>{title}</span>
          <SukoonTrustTierPill score={score} className={styles.trustTierPillHero || ""} />
          <p className={styles.trustCardSubcopy || ""}>
            Your Sukoon trust level reflects completed verifications and earned badges.
          </p>
        </div>
      </div>

      {publicTrust.badges?.length ? (
        <div className={styles.trustCardSection || ""}>
          <h4 className={styles.trustCardSectionTitle || ""}>Verification badges</h4>
          <SukoonTrustBadges badges={publicTrust.badges} variant="card" publicOnly />
        </div>
      ) : null}
    </div>
  );
}

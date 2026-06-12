"use client";

import {
  FiAward,
  FiCheckCircle,
  FiShield,
  FiStar,
  FiUserCheck,
} from "react-icons/fi";
import styles from "./trustVerificationPremium.module.css";

const SLUG_ICONS = {
  trusted_owner: FiShield,
  owner_verified: FiUserCheck,
  premium_verified: FiAward,
  sukoon_elite_verified: FiStar,
  identity_verified: FiCheckCircle,
  reference_verified: FiCheckCircle,
  police_verified: FiShield,
  address_verified: FiCheckCircle,
  documents_verified: FiCheckCircle,
};

function BadgeIcon({ slug }) {
  const Icon = SLUG_ICONS[slug] || FiAward;
  return <Icon className={styles.trustBadgeCardIconSvg || ""} aria-hidden />;
}

function isInternalScoringCopy(text) {
  if (!text || typeof text !== "string") return false;
  return /\+\s*\d|pts?\b|points?\b|weight|raw_total|admin approval|no rejected/i.test(text);
}

function publicBadgeDescription(badge, publicOnly) {
  if (!badge?.description) return null;
  if (publicOnly && isInternalScoringCopy(badge.description)) {
    return null;
  }
  return badge.description;
}

/**
 * @param {{ badges?: Array, max?: number, variant?: 'card' | 'chip', publicOnly?: boolean }} props
 */
export default function SukoonTrustBadges({
  badges = [],
  max = 8,
  variant = "card",
  publicOnly = false,
}) {
  const list = (badges || []).filter((b) => b?.name).slice(0, max);
  if (!list.length) return null;

  if (variant === "chip") {
    return (
      <div className={styles.trustBadgesChips || ""} role="list" aria-label="Trust badges">
        {list.map((badge) => (
          <span
            key={badge.slug || badge.name}
            className={styles.trustBadgeChip || ""}
            role="listitem"
            title={badge.name}
          >
            <BadgeIcon slug={badge.slug} />
            {badge.name}
          </span>
        ))}
      </div>
    );
  }

  return (
    <div className={styles.trustBadgesGrid || ""} role="list" aria-label="Trust badges">
      {list.map((badge) => {
        const desc = publicBadgeDescription(badge, publicOnly);
        return (
          <article
            key={badge.slug || badge.name}
            className={styles.trustBadgeCard || ""}
            role="listitem"
          >
            <div className={styles.trustBadgeCardIcon || ""}>
              <BadgeIcon slug={badge.slug} />
            </div>
            <div className={styles.trustBadgeCardContent || ""}>
              <h4 className={styles.trustBadgeCardTitle || ""}>{badge.name}</h4>
              {desc ? <p className={styles.trustBadgeCardDesc || ""}>{desc}</p> : null}
            </div>
          </article>
        );
      })}
    </div>
  );
}

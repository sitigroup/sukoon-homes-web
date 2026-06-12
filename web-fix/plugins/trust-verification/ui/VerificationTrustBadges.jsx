"use client";

import { cn } from "./trustVerificationTheme";
import styles from "./trustVerificationPremium.module.css";

const BADGES = [
  { icon: "🔒", label: "Secure verification" },
  { icon: "🛡️", label: "Privacy protected" },
  { icon: "✉️", label: "Report by email" },
];

export default function VerificationTrustBadges({ className = "" }) {
  return (
    <div className={cn(styles.trustBadges, className)}>
      {BADGES.map((b) => (
        <span key={b.label} className={styles.trustBadge}>
          <span aria-hidden>{b.icon}</span>
          {b.label}
        </span>
      ))}
    </div>
  );
}

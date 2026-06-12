"use client";

import styles from "./trustVerificationPremium.module.css";

/**
 * Scoped light premium wrapper — Trust Verification routes only.
 */
export default function TrustVerificationPremiumShell({ children, className = "" }) {
  return (
    <div className={`${styles.page} ${className}`.trim()}>
      <div className={styles.pageGlow} aria-hidden />
      <div className={styles.pageInner}>{children}</div>
    </div>
  );
}

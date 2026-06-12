"use client";

import styles from "./trustVerificationPremium.module.css";

export default function VerificationEmptyState({
  title,
  description,
  action = null,
  className = "",
}) {
  return (
    <div className={`${styles.emptyState} ${className}`.trim()}>
      <p className={styles.emptyTitle}>{title}</p>
      {description && <p className={styles.emptyDesc}>{description}</p>}
      {action ? <div className="mt-4 flex justify-center">{action}</div> : null}
    </div>
  );
}

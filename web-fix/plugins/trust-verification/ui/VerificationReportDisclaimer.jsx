"use client";

import { TV_REPORT_DISCLAIMER_TEXT } from "./trustVerificationLegalCopy";
import styles from "./trustVerificationPremium.module.css";

export default function VerificationReportDisclaimer({
  className = "",
  compact = false,
  text = TV_REPORT_DISCLAIMER_TEXT,
}) {
  return (
    <p
      className={`${styles.disclaimerNote} ${compact ? styles.disclaimerNoteCompact : ""} ${className}`.trim()}
      role="note"
    >
      <span className={styles.disclaimerNoteStrong}>Disclaimer: </span>
      {text}
    </p>
  );
}

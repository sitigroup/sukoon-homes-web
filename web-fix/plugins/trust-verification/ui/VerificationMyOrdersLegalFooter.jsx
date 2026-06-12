"use client";

import Link from "next/link";
import { myVerificationOrdersReturnPath } from "../verificationLegalNavigation";
import { trustVerificationLegalLinks } from "./trustVerificationLegalCopy";
import styles from "./trustVerificationPremium.module.css";

export default function VerificationMyOrdersLegalFooter({ lang = "en", className = "" }) {
  const returnTo = myVerificationOrdersReturnPath(lang);
  const links = trustVerificationLegalLinks(lang, returnTo);

  return (
    <nav
      className={`${styles.myOrdersLegalFooterCompact} ${className}`.trim()}
      aria-label="Verification legal policies"
    >
      {links.map((item, index) => (
        <span key={item.label} className={styles.myOrdersLegalFooterItem}>
          {index > 0 ? (
            <span className={styles.myOrdersLegalFooterSep} aria-hidden>
              {" "}
              ·{" "}
            </span>
          ) : null}
          <Link href={item.href} className={styles.myOrdersLegalFooterLink}>
            {item.label}
          </Link>
        </span>
      ))}
    </nav>
  );
}

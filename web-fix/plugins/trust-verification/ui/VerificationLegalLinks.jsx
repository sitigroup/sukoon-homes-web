"use client";

import Link from "next/link";
import { trustVerificationLegalLinks } from "./trustVerificationLegalCopy";
import styles from "./trustVerificationPremium.module.css";

export default function VerificationLegalLinks({
  lang = "en",
  returnTo,
  className = "",
  centered = false,
}) {
  const links = trustVerificationLegalLinks(lang, returnTo);

  return (
    <nav
      className={`${styles.legalLinks} ${centered ? styles.legalLinksCenter : ""} ${className}`.trim()}
      aria-label="Verification legal policies"
    >
      {links.map((item) => (
        <Link key={item.label} href={item.href} className={styles.legalLink}>
          {item.label}
        </Link>
      ))}
    </nav>
  );
}

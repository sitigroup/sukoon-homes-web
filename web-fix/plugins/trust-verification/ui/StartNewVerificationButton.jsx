"use client";

import Link from "next/link";
import { BiCheckShield } from "react-icons/bi";
import styles from "./trustVerificationPremium.module.css";

export default function StartNewVerificationButton({ lang = "en", className = "" }) {
  return (
    <Link
      href={`/verification?lang=${lang}`}
      className={`${styles.startNewVerificationBtn} ${className}`.trim()}
    >
      <BiCheckShield className={styles.startNewVerificationIcon} aria-hidden />
      Start New Verification
    </Link>
  );
}

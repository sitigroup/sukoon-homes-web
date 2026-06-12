"use client";

import { FiAward } from "react-icons/fi";
import styles from "./trustVerificationPremium.module.css";

/** Active SVO/SVT badges on profile (private tenant + owner). */
export default function VerificationIssuedBadgesList({ badges = [] }) {
  const rows = Array.isArray(badges) ? badges.filter((b) => b?.is_verified) : [];
  if (!rows.length) return null;

  return (
    <div className={styles.verificationIssuedList || "mt-4"}>
      <h4 className="mb-2 text-sm font-semibold text-[#374151]">Verification badges</h4>
      <ul className="space-y-2">
        {rows.map((badge) => (
          <li
            key={badge.id}
            className="flex items-center gap-2 rounded-lg border border-[#E5E7EB] bg-white px-3 py-2 text-sm"
          >
            <FiAward className="shrink-0 text-[#111827]" aria-hidden />
            <span>
              <strong>{badge.badge_title}</strong>
              <span className="ml-2 text-xs text-[#6B7280]">
                <code>{badge.badge_number}</code>
              </span>
            </span>
          </li>
        ))}
      </ul>
    </div>
  );
}

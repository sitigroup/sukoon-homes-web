"use client";

import { useState } from "react";
import { FiAward, FiDownload } from "react-icons/fi";
import { downloadVerificationBadgeCertificate } from "../trustVerificationApi";
import styles from "./trustVerificationPremium.module.css";

/**
 * Completed-order issued badge (SVO / SVT). Uses order.verification_badge from API.
 */
export default function VerificationOrderBadgePanel({ order }) {
  const badge = order?.verification_badge;
  const [downloading, setDownloading] = useState(false);

  if (!badge || order?.status !== "completed") {
    return null;
  }

  if (!badge.is_verified && badge.status !== "pending") {
    return null;
  }

  const handleDownload = async () => {
    if (!badge?.id || !badge.can_download) return;
    setDownloading(true);
    try {
      await downloadVerificationBadgeCertificate(badge.id);
    } catch {
      /* toast handled by caller if needed */
    } finally {
      setDownloading(false);
    }
  };

  return (
    <div className={styles.verificationOrderBadgePanel || "mt-4 rounded-xl border border-[#E5E7EB] bg-[#FAFAFA] p-4"}>
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <p className="mb-1 flex items-center gap-2 text-sm font-semibold text-[#111827]">
            <FiAward aria-hidden />
            {badge.badge_title || "Verification badge"}
          </p>
          <p className="text-xs text-[#6B7280]">
            <code>{badge.badge_number}</code>
            {badge.status === "pending" ? " · Pending verification" : null}
          </p>
        </div>
        {badge.can_download ? (
          <button
            type="button"
            className={styles.verificationBadgeDownloadBtn || "inline-flex items-center gap-2 rounded-lg border border-[#111827] px-3 py-2 text-xs font-semibold text-[#111827]"}
            onClick={handleDownload}
            disabled={downloading}
          >
            <FiDownload aria-hidden />
            {downloading ? "Preparing…" : "Download badge"}
          </button>
        ) : null}
      </div>
    </div>
  );
}

"use client";

import { useState } from "react";
import { useTrustVerificationContent } from "../useTrustVerificationContent";
import SampleReportModal from "./SampleReportModal";
import { tv, cn } from "./trustVerificationTheme";

export default function SampleReportTrigger({
  reportType = "tenant",
  citySlug = "",
  packageId = null,
  buttonText = "",
  className = "",
  variant = "secondary",
  source = "web",
  stopPropagation = true,
}) {
  const [open, setOpen] = useState(false);
  const { content } = useTrustVerificationContent();
  const label =
    buttonText ||
    content?.hub?.sample_report_button_text ||
    "View Sample Report";

  const btnClass =
    variant === "link"
      ? "text-sm font-semibold text-[#1F2937] underline-offset-2 hover:underline"
      : variant === "primary"
        ? cn(tv.btnPrimary, "text-sm")
        : cn(tv.btnSecondary, "text-sm");

  return (
    <>
      <button
        type="button"
        className={cn(btnClass, className)}
        onClick={(e) => {
          if (stopPropagation) e.stopPropagation();
          setOpen(true);
        }}
      >
        {label}
      </button>
      <SampleReportModal
        open={open}
        onClose={() => setOpen(false)}
        reportType={reportType}
        citySlug={citySlug}
        packageId={packageId}
        source={source}
      />
    </>
  );
}

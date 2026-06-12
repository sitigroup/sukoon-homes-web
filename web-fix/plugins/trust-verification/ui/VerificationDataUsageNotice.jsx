"use client";

import { cn, tv } from "./trustVerificationTheme";
import { TV_DATA_USAGE_TEXT } from "./trustVerificationLegalCopy";

export default function VerificationDataUsageNotice({
  className = "",
  compact = false,
  text = TV_DATA_USAGE_TEXT,
}) {
  return (
    <p
      className={cn(
        "rounded-[12px] border border-[#E5E7EB] bg-[#F8F9FA] text-[#6B7280]",
        compact ? "px-3 py-2 text-[11px] leading-snug" : "px-3 py-2.5 text-xs leading-relaxed",
        className
      )}
      role="note"
    >
      <span className={cn(tv.accent, "mr-1 font-medium")}>Data use:</span>
      {text}
    </p>
  );
}

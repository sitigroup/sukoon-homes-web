"use client";

import { cn, tv } from "./trustVerificationTheme";
import { TV_CONSENT_TEXT } from "./trustVerificationLegalCopy";

export default function VerificationConsentCheckbox({
  checked,
  onChange,
  className = "",
  text = TV_CONSENT_TEXT,
}) {
  return (
    <label
      className={cn(
        "flex cursor-pointer gap-3 rounded-[12px] border border-[#E5E7EB] bg-[#FFFFFF] p-3 text-left transition hover:bg-[#F3F4F6]",
        className
      )}
    >
      <input
        type="checkbox"
        className="mt-0.5 h-4 w-4 shrink-0 rounded border-[#D1D5DB] text-[#B89A4A] focus:ring-[#B89A4A]"
        checked={!!checked}
        onChange={(e) => onChange?.(e.target.checked)}
      />
      <span className={cn(tv.subheading, "text-xs leading-relaxed sm:text-sm")}>
        {text}
      </span>
    </label>
  );
}

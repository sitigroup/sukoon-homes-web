"use client";

import { cn, tv } from "./trustVerificationTheme";

const VARIANTS = {
  submitted: "border-[#E5E7EB] bg-[#F3F4F6] text-[#6B7280]",
  in_progress: "border-[rgba(184,154,74,0.28)] bg-[rgba(184,154,74,0.08)] text-[#8F7840]",
  completed: "border-emerald-200 bg-emerald-50 text-emerald-700",
  cancelled: "border-red-200 bg-red-50 text-red-700",
  pending: "border-amber-200 bg-amber-50 text-amber-800",
  paid: "border-emerald-200 bg-emerald-50 text-emerald-700",
  waived: "border-[#E5E7EB] bg-[#F3F4F6] text-[#6B7280]",
  failed: "border-red-200 bg-red-50 text-red-700",
  default: tv.badge,
};

export default function VerificationStatusBadge({ label, variant = "default", className = "" }) {
  const key = String(variant || "default").toLowerCase().replace(/\s+/g, "_");
  const styles = VARIANTS[key] || VARIANTS.default;

  return (
    <span
      className={cn(
        "inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium capitalize",
        styles,
        className
      )}
    >
      {label || variant}
    </span>
  );
}

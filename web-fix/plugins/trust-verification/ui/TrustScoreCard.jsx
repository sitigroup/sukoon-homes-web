"use client";

import TrustCard from "./TrustCard";
import { cn, tv } from "./trustVerificationTheme";

const RISK = {
  green: { label: "Low risk", color: "text-emerald-600", bar: "bg-emerald-500", pct: "85%" },
  amber: { label: "Review advised", color: "text-amber-600", bar: "bg-amber-500", pct: "55%" },
  red: { label: "High risk", color: "text-red-600", bar: "bg-red-500", pct: "25%" },
  default: { label: "Pending analysis", color: "text-[#6B7280]", bar: "bg-[#E5E7EB]", pct: "—" },
};

export default function TrustScoreCard({
  scoreLabel = "Trust score",
  riskLevel,
  summary,
  className = "",
}) {
  const meta = RISK[riskLevel] || RISK.default;

  return (
    <TrustCard className={className} accent>
      <p className={cn(tv.muted, "text-xs uppercase tracking-wider")}>{scoreLabel}</p>
      <p className={cn("mt-2 text-2xl font-bold", meta.color)}>{meta.label}</p>
      <div className="mt-4 h-2 overflow-hidden rounded-full bg-[#E5E7EB]">
        <div
          className={cn("h-full rounded-full transition-all", meta.bar)}
          style={{
            width:
              riskLevel === "green"
                ? "85%"
                : riskLevel === "amber"
                  ? "55%"
                  : riskLevel === "red"
                    ? "25%"
                    : "10%",
          }}
        />
      </div>
      {summary && <p className={cn(tv.subheading, "mt-4 text-sm leading-relaxed")}>{summary}</p>}
    </TrustCard>
  );
}

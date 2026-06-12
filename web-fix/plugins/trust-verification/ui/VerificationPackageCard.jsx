"use client";

import TrustCard from "./TrustCard";
import SampleReportTrigger from "./SampleReportTrigger";
import { tv, cn, TV_GOLD, TV_CHECK_BG, TV_RECOMMENDED_BG } from "./trustVerificationTheme";

export default function VerificationPackageCard({
  name,
  price,
  priceSuffix = "+ GST",
  deliveryHours,
  features = [],
  selected = false,
  recommended = false,
  onSelect,
  className = "",
  showSampleReport = false,
  sampleReportType = "tenant",
  citySlug = "",
  packageId = null,
}) {
  return (
    <button
      type="button"
      onClick={onSelect}
      className={cn("w-full text-left", className)}
    >
      <TrustCard
        padding="p-5 sm:p-6"
        hover
        accent={selected}
        className={cn(
          "relative h-full",
          selected && "ring-2 ring-[#B89A4A]/28"
        )}
      >
        {recommended && (
          <span
            className="absolute -top-2 right-4 rounded-full px-3 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-[#1F2937] shadow-sm"
            style={{ backgroundColor: TV_RECOMMENDED_BG }}
          >
            Recommended
          </span>
        )}
        <div className="flex items-start justify-between gap-2">
          <h3 className={cn(tv.heading, "text-lg")}>{name}</h3>
          {selected && (
            <span
              className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
              style={{ backgroundColor: TV_CHECK_BG, color: TV_GOLD }}
            >
              ✓
            </span>
          )}
        </div>
        <p className="mt-3 text-3xl font-bold tracking-tight text-[#111827]">
          {price}
          {priceSuffix && (
            <span className="ml-1 text-sm font-normal text-[#6B7280]">{priceSuffix}</span>
          )}
        </p>
        {deliveryHours && (
          <p className={cn(tv.muted, "mt-1")}>Report in {deliveryHours} hours</p>
        )}
        {features.length > 0 && (
          <ul className="mt-4 space-y-2 border-t border-[#E5E7EB] pt-4 text-sm">
            {features.map((f) => (
              <li
                key={f.key || f.label}
                className={cn(
                  "flex gap-2",
                  f.included ? "text-[#6B7280]" : "text-[#9CA3AF] line-through"
                )}
              >
                <span className={f.included ? "text-[#B89A4A]" : "text-[#D1D5DB]"}>
                  {f.included ? "✓" : "—"}
                </span>
                <span className="min-w-0 flex-1 break-words">{f.label}</span>
              </li>
            ))}
          </ul>
        )}
        {showSampleReport && (
          <div className="mt-4 border-t border-[#E5E7EB] pt-3">
            <SampleReportTrigger
              reportType={sampleReportType}
              citySlug={citySlug}
              packageId={packageId}
              source="package-card"
              className="w-full justify-center"
            />
          </div>
        )}
      </TrustCard>
    </button>
  );
}

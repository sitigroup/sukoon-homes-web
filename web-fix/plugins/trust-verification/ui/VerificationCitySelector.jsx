"use client";

import { cn, tv } from "./trustVerificationTheme";

export default function VerificationCitySelector({
  cities = [],
  selectedSlug,
  onSelect,
  className = "",
}) {
  if (!cities.length) return null;

  return (
    <div className={cn("mb-6", className)}>
      <p className={cn(tv.muted, "mb-2 text-xs uppercase tracking-wider")}>Select city</p>
      <div className="flex flex-wrap gap-2">
        {cities.map((city) => {
          const active = selectedSlug === city.slug;
          return (
            <button
              key={city.slug}
              type="button"
              onClick={() => onSelect?.(city.slug)}
              className={cn(
                "rounded-[12px] border px-4 py-2 text-sm font-medium transition",
                active
                  ? "border-[#B89A4A] bg-[rgba(184,154,74,0.08)] text-[#8F7840]"
                  : "border-[#E5E7EB] bg-[#FFFFFF] text-[#6B7280] hover:border-[#D1D5DB] hover:bg-[#F3F4F6]"
              )}
            >
              {city.label}
            </button>
          );
        })}
      </div>
    </div>
  );
}

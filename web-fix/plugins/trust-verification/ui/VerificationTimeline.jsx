"use client";

import { cn, tv } from "./trustVerificationTheme";

const DOT = {
  complete: "bg-[#1F2937] border-[#1F2937] text-[#FFFFFF]",
  current:
    "bg-[#FFFFFF] border-[#1F2937] text-[#1F2937] ring-2 ring-[#B89A4A] ring-offset-2",
  upcoming: "bg-[#F8F9FA] border-[#E5E7EB] text-[#9CA3AF]",
};

export default function VerificationTimeline({ steps = [], className = "", compact = false }) {
  return (
    <div className={cn(tv.card, "p-5 sm:p-6", className)}>
      <h3 className={cn(tv.heading, compact ? "text-sm" : "text-base", "mb-5")}>
        Order progress
      </h3>
      <ol className="relative space-y-0">
        {steps.map((step, index) => {
          const isLast = index === steps.length - 1;
          return (
            <li key={step.id} className="relative flex gap-4 pb-8 last:pb-0">
              {!isLast && (
                <span
                  className={cn(
                    "absolute left-[15px] top-8 h-[calc(100%-8px)] w-0.5",
                    step.status === "complete" ? "bg-[#1F2937]" : "bg-[#E5E7EB]"
                  )}
                  aria-hidden
                />
              )}
              <span
                className={cn(
                  "relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 text-xs font-bold",
                  DOT[step.status] || DOT.upcoming
                )}
              >
                {step.status === "complete" ? "✓" : index + 1}
              </span>
              <div className="min-w-0 flex-1 pt-0.5">
                <p
                  className={cn(
                    "font-medium",
                    step.status === "upcoming" ? "text-[#9CA3AF]" : "text-[#111827]"
                  )}
                >
                  {step.label}
                </p>
                {step.status === "current" && (
                  <p className="mt-0.5 text-xs text-[#B89A4A]">In progress</p>
                )}
              </div>
            </li>
          );
        })}
      </ol>
    </div>
  );
}

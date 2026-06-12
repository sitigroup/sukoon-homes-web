"use client";

import { cn, tv } from "./trustVerificationTheme";

const LABELS = ["Package", "Details", "Review", "Pay", "Done"];

export default function VerificationProgressStepper({
  currentStep = 1,
  totalSteps = 4,
  labels = LABELS,
  className = "",
}) {
  const steps = labels.slice(0, totalSteps);

  return (
    <div className={cn("mb-6", className)}>
      <div className="flex gap-1">
        {steps.map((_, i) => {
          const n = i + 1;
          const active = n === currentStep;
          const reached = n <= currentStep;
          return (
            <div
              key={n}
              className={cn(
                "relative h-1 flex-1 rounded-full transition-colors",
                reached && !active && "bg-[#1F2937]",
                active && "bg-[#1F2937]",
                !reached && "bg-[#E5E7EB]"
              )}
            >
              {active && (
                <span
                  className="absolute -top-0.5 right-0 h-2 w-2 translate-x-1/2 rounded-full bg-[#B89A4A] ring-2 ring-white"
                  aria-hidden
                />
              )}
            </div>
          );
        })}
      </div>
      <div className="mt-2 hidden justify-between text-[10px] text-[#9CA3AF] sm:flex">
        {steps.map((label, i) => {
          const n = i + 1;
          const active = n === currentStep;
          const done = n < currentStep;
          return (
            <span
              key={label}
              className={cn(
                active && "text-[#B89A4A] font-semibold",
                done && !active && "text-[#1F2937] font-medium",
                n > currentStep && ""
              )}
            >
              {label}
            </span>
          );
        })}
      </div>
      <p className={cn(tv.muted, "mt-1 sm:hidden")}>
        Step {Math.min(currentStep, totalSteps)} of {totalSteps}
      </p>
    </div>
  );
}

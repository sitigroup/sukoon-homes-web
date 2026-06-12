"use client";

import { cn } from "./trustVerificationTheme";
import styles from "./trustVerificationPremium.module.css";

function dotClass(status) {
  if (status === "complete") {
    return "bg-[#1F2937] border-[#1F2937] text-white";
  }
  if (status === "current") {
    return "bg-white border-[#1F2937] text-[#1F2937] ring-2 ring-[#B89A4A] ring-offset-2";
  }
  return "bg-[#F8F9FA] border-[#D1D5DB] text-[#9CA3AF]";
}

function lineClass(status) {
  return status === "complete" ? "bg-[#1F2937]" : "bg-[#D1D5DB]";
}

function titleClass(status) {
  if (status === "upcoming") return styles.orderProgressTitleMuted;
  return styles.orderProgressTitle;
}

function detailClass(status) {
  if (status === "upcoming") return styles.orderProgressDetailMuted;
  return styles.orderProgressDetail;
}

function DesktopStepper({ steps }) {
  return (
    <div className={styles.orderProgressRow} role="list" aria-label="Order progress">
      {steps.map((step, index) => (
        <div key={step.id} className={styles.orderProgressStep} role="listitem">
          <div className={styles.orderProgressTrack}>
            <span
              className={cn(styles.orderProgressDot, dotClass(step.status))}
              aria-hidden
            >
              {step.status === "complete" ? "✓" : index + 1}
            </span>
            {index < steps.length - 1 && (
              <span
                className={cn(styles.orderProgressLine, lineClass(step.status))}
                aria-hidden
              />
            )}
          </div>
          <div className={styles.orderProgressStepText}>
            <span className={titleClass(step.status)}>{step.label}</span>
            {step.detail ? (
              <span className={detailClass(step.status)}>{step.detail}</span>
            ) : null}
          </div>
        </div>
      ))}
    </div>
  );
}

function MobileTimeline({ steps }) {
  return (
    <div className={styles.orderProgressVertical} role="list" aria-label="Order progress">
      {steps.map((step, index) => {
        const isLast = index === steps.length - 1;

        return (
          <div key={step.id} className={styles.orderProgressVerticalItem} role="listitem">
            <div className={styles.orderProgressVerticalRail}>
              <span
                className={cn(styles.orderProgressDot, dotClass(step.status))}
                aria-hidden
              >
                {step.status === "complete" ? "✓" : index + 1}
              </span>
              {!isLast && (
                <span
                  className={cn(
                    styles.orderProgressVerticalStem,
                    step.status === "complete" ? styles.orderProgressVerticalStemComplete : ""
                  )}
                  aria-hidden
                />
              )}
            </div>
            <div className={styles.orderProgressVerticalBody}>
              <span className={titleClass(step.status)}>{step.label}</span>
              {step.detail ? (
                <span className={cn(detailClass(step.status), "mt-1 block")}>
                  {step.detail}
                </span>
              ) : null}
            </div>
          </div>
        );
      })}
    </div>
  );
}

/** Premium progress timeline for My Verification Orders */
export default function OrderProgressStrip({ steps = [], className = "" }) {
  if (!steps.length) return null;

  return (
    <div className={cn(styles.orderProgressCard, className)}>
      <div className={styles.orderProgressDesktop}>
        <DesktopStepper steps={steps} />
      </div>
      <div className={styles.orderProgressMobile}>
        <MobileTimeline steps={steps} />
      </div>
    </div>
  );
}

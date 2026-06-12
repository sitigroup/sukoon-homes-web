"use client";

import Link from "next/link";
import TrustCard from "./TrustCard";
import VerificationReportDisclaimer from "./VerificationReportDisclaimer";
import VerificationLegalLinks from "./VerificationLegalLinks";
import { tv, cn } from "./trustVerificationTheme";

export default function VerificationSuccessScreen({
  title = "Request received",
  message,
  orderNumber,
  primaryHref,
  primaryLabel = "View my orders",
  secondaryHref,
  secondaryLabel,
  lang = "en",
  returnTo,
  showDisclaimer = true,
  showLegalLinks = true,
  className = "",
}) {
  return (
    <TrustCard className={cn("text-center", className)} accent padding="p-6 sm:p-8">
      <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[#F5F3EA] text-3xl text-[#B89A4A]">
        ✓
      </div>
      <h3 className={cn(tv.heading, "mt-4 text-xl")}>{title}</h3>
      {orderNumber && (
        <p className="mt-2 font-mono text-sm text-[#B89A4A]">{orderNumber}</p>
      )}
      {message && <p className={cn(tv.subheading, "mx-auto mt-3 max-w-sm text-sm")}>{message}</p>}
      {showDisclaimer && (
        <VerificationReportDisclaimer className="mx-auto mt-4 max-w-md text-left" />
      )}
      <div className="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-center">
        {primaryHref && (
          <Link href={primaryHref} className={tv.btnPrimary}>
            {primaryLabel}
          </Link>
        )}
        {secondaryHref && secondaryLabel && (
          <Link href={secondaryHref} className={tv.btnSecondary}>
            {secondaryLabel}
          </Link>
        )}
      </div>
      {showLegalLinks && (
        <VerificationLegalLinks
          lang={lang}
          returnTo={returnTo}
          centered
          className={cn("mt-6 border-t pt-4", tv.divider)}
        />
      )}
    </TrustCard>
  );
}

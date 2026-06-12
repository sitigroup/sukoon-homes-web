"use client";

import { tv, cn } from "./trustVerificationTheme";
import VerificationTrustBadges from "./VerificationTrustBadges";

export default function VerificationHubHero({ title, subtitle, actions, className = "" }) {
  return (
    <header className={cn("mb-8 sm:mb-10", className)}>
      <p className={tv.badge}>Sukoon Homes Trust Verification</p>
      <h1 className={cn(tv.heading, "mt-4 text-3xl sm:text-4xl lg:text-[2.75rem] lg:leading-tight")}>
        {title}
      </h1>
      {subtitle && (
        <p className={cn(tv.subheading, "mt-3 max-w-2xl text-base sm:text-lg leading-relaxed")}>
          {subtitle}
        </p>
      )}
      <div className="mt-6">
        <VerificationTrustBadges />
      </div>
      {actions && <div className="mt-6">{actions}</div>}
    </header>
  );
}

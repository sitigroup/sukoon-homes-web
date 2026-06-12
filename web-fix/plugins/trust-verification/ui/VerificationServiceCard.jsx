"use client";

import Link from "next/link";
import TrustCard from "./TrustCard";
import { tv, cn } from "./trustVerificationTheme";

export default function VerificationServiceCard({
  href,
  title,
  description,
  cta = "Get started",
  icon = "→",
  variant = "tenant",
}) {
  const accent =
    variant === "tenant"
      ? "from-[rgba(184,154,74,0.05)] to-transparent"
      : "from-[rgba(0,0,0,0.02)] to-transparent";

  return (
    <Link href={href} className="block h-full group">
      <TrustCard
        hover
        padding="p-5 sm:p-6"
        className={cn("h-full bg-gradient-to-br", accent)}
      >
        <span className="text-2xl text-[#B89A4A]" aria-hidden>
          {variant === "tenant" ? "🏠" : "🔑"}
        </span>
        <h3 className={cn(tv.heading, "mt-3 text-lg group-hover:text-[#1F2937] transition")}>
          {title}
        </h3>
        <p className={cn(tv.subheading, "mt-2 text-sm leading-relaxed")}>{description}</p>
        <span className="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-[#1F2937]">
          {cta} <span className="text-[#B89A4A]">{icon}</span>
        </span>
      </TrustCard>
    </Link>
  );
}

"use client";

import Link from "next/link";
import { useRouter } from "next/router";
import { useAuthStatus } from "@/hooks/useAuthStatus";

const LABEL = "My Verification Orders";

export default function MyVerificationOrdersLink({
  lang = "en",
  className = "",
  variant = "default",
  fullWidth = false,
}) {
  const isLoggedIn = useAuthStatus();
  const router = useRouter();
  if (!isLoggedIn) return null;

  const href = `/my-verification-orders?lang=${lang || "en"}`;
  const isActive =
    router.pathname === "/my-verification-orders" ||
    router.asPath?.startsWith("/my-verification-orders");

  if (className) {
    return (
      <Link
        href={href}
        className={className}
        aria-current={isActive ? "page" : undefined}
      >
        {LABEL}
      </Link>
    );
  }

  const compact =
    variant === "compact"
      ? "rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-medium text-gray-800 shadow-sm transition-colors hover:border-[rgba(184,154,74,0.45)] hover:bg-[rgba(184,154,74,0.06)] sm:text-sm"
      : "rounded-lg border brandBorder px-4 py-2 text-sm font-medium brandColor bg-white hover:brandBgLight";

  const activeRing = isActive ? " !border-[#B89A4A] !bg-[rgba(184,154,74,0.12)] !text-[#8f7840]" : "";

  return (
    <Link
      href={href}
      className={`inline-flex items-center justify-center ${compact}${activeRing}${
        fullWidth ? " w-full" : ""
      }`}
      aria-current={isActive ? "page" : undefined}
    >
      {LABEL}
    </Link>
  );
}

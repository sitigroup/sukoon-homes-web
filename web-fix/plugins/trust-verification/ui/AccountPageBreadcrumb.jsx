"use client";

import Link from "next/link";
import { HiMiniSlash } from "react-icons/hi2";

/**
 * Compact in-card breadcrumb for account-area pages (matches user dashboard, not marketing hero).
 */
export default function AccountPageBreadcrumb({
  title,
  items = [],
  lang = "en",
  action = null,
  className = "",
}) {
  return (
    <header
      className={`border-b border-[#E5E7EB] px-4 py-4 sm:px-6 sm:py-5 ${className}`.trim()}
    >
      <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
        <div className="min-w-0 flex-1">
          <h1 className="text-xl font-bold text-[#111827] sm:text-2xl">{title}</h1>
          <nav className="mt-2" aria-label="Breadcrumb">
            <ol className="flex flex-wrap items-center gap-0.5 text-sm text-[#6B7280]">
              <li className="flex items-center">
                <Link
                  href={`/?lang=${lang}`}
                  className="font-medium text-[#374151] transition-colors hover:text-[#B89A4A]"
                >
                  Home
                </Link>
              </li>
              {items.map((item, index) => (
                <li key={item.href || item.label} className="flex items-center">
                  <HiMiniSlash className="mx-0.5 h-4 w-4 shrink-0 text-[#9CA3AF]" aria-hidden />
                  {index === items.length - 1 ? (
                    <span className="font-medium text-[#B89A4A]" aria-current="page">
                      {item.label}
                    </span>
                  ) : (
                    <Link
                      href={item.href}
                      className="font-medium text-[#374151] transition-colors hover:text-[#B89A4A]"
                    >
                      {item.label}
                    </Link>
                  )}
                </li>
              ))}
            </ol>
          </nav>
        </div>
        {action ? <div className="w-full shrink-0 sm:w-auto sm:self-center">{action}</div> : null}
      </div>
    </header>
  );
}

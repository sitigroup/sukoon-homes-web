"use client";

import AccountPageBreadcrumb from "./AccountPageBreadcrumb";

/**
 * Account dashboard shell for My Verification Orders (sidebar + white content card).
 */
export default function MyVerificationOrdersAccountLayout({
  lang = "en",
  headerAction = null,
  children,
  className = "",
}) {
  return (
    <div
      className={`flex min-h-[480px] w-full flex-col overflow-hidden rounded-2xl border border-[#E5E7EB] bg-white shadow-sm ${className}`.trim()}
    >
      <AccountPageBreadcrumb
        title="My Verification Orders"
        lang={lang}
        action={headerAction}
        items={[
          { href: `/user/profile?lang=${lang}`, label: "Account" },
          { href: `/my-verification-orders?lang=${lang}`, label: "My Verification Orders" },
        ]}
      />
      <div className="flex-1 px-4 py-5 sm:px-6 sm:py-6">{children}</div>
    </div>
  );
}

"use client";

import { useEffect } from "react";
import { useRouter } from "next/router";

const paidLinkStatuses = new Set(["PAID", "PARTIALLY_PAID"]);
const failedLinkStatuses = new Set(["FAILED", "CANCELLED", "CANCELED", "EXPIRED"]);

function resolvePaymentStatus(query) {
  const linkStatus = String(query?.link_status || query?.linkStatus || "").toUpperCase();
  if (paidLinkStatuses.has(linkStatus)) return "success";
  if (failedLinkStatuses.has(linkStatus)) return "failed";

  const txStatus = String(query?.txStatus || query?.tx_status || "").toUpperCase();
  if (txStatus === "SUCCESS") return "success";
  if (txStatus) return "failed";

  // Cashfree link return often has no explicit status; webhook marks paid — treat as success for UX.
  return "success";
}

export default function TrustVerificationPaymentReturn() {
  const router = useRouter();

  useEffect(() => {
    if (!router.isReady) return;

    const status = resolvePaymentStatus(router.query);
    const payload = {
      status,
      gateway: "cashfree",
      reference: router.query?.referenceId || router.query?.link_id || null,
    };

    const openerTargets = [
      process.env.NEXT_PUBLIC_WEB_URL,
      process.env.NEXT_PUBLIC_API_URL,
      typeof window !== "undefined" ? window.location.origin : null,
    ].filter(Boolean);

    if (typeof window !== "undefined" && window.opener && !window.opener.closed) {
      openerTargets.forEach((origin) => {
        try {
          window.opener.postMessage(payload, origin);
        } catch {
          /* ignore cross-origin postMessage errors */
        }
      });
      window.close();
      return;
    }

    const lang = router.query?.lang || "en";
    const suffix = status === "success" ? "payment=success" : "payment=failed";
    const orderId = router.query?.order_id;
    const orderQuery = orderId ? `&order_id=${encodeURIComponent(String(orderId))}` : "";
    router.replace(`/my-verification-orders?lang=${lang}&${suffix}${orderQuery}`);
  }, [router.isReady, router.query, router]);

  return (
    <div className="flex min-h-[50vh] flex-col items-center justify-center p-8 text-center">
      <p className="text-lg font-medium text-gray-800">Completing your payment…</p>
      <p className="mt-2 text-sm text-gray-500">You can close this window if it does not close automatically.</p>
    </div>
  );
}

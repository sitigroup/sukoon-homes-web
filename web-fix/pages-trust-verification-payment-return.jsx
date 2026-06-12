"use client";

import dynamic from "next/dynamic";

const TrustVerificationPaymentReturn = dynamic(
  () => import("@/plugins/trust-verification/TrustVerificationPaymentReturn"),
  { ssr: false }
);

export default function TrustVerificationPaymentReturnPage() {
  return <TrustVerificationPaymentReturn />;
}

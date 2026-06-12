"use client";

import Layout from "@/components/layout/Layout";

/** Wraps trust verification pages with site Header + Footer (same as Contact/About). */
export default function TrustVerificationSiteLayout({ children }) {
  return <Layout>{children}</Layout>;
}

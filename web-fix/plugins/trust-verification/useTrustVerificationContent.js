"use client";

import { useEffect, useState } from "react";
import { getTrustVerificationPublicContent } from "./trustVerificationApi";
import { TRUST_VERIFICATION_CONTENT_FALLBACK } from "./trustVerificationContentFallback";
import { mergeTrustVerificationContent } from "./trustVerificationContentMerge";

let cachedContent = null;
let cachePromise = null;

export function useTrustVerificationContent() {
  const [content, setContent] = useState(
    cachedContent || TRUST_VERIFICATION_CONTENT_FALLBACK
  );
  const [ready, setReady] = useState(!!cachedContent);

  useEffect(() => {
    if (cachedContent) {
      setContent(cachedContent);
      setReady(true);
      return;
    }
    if (!cachePromise) {
      cachePromise = getTrustVerificationPublicContent()
        .then((res) => mergeTrustVerificationContent(res?.data))
        .catch(() => TRUST_VERIFICATION_CONTENT_FALLBACK);
    }
    cachePromise.then((merged) => {
      cachedContent = merged;
      setContent(merged);
      setReady(true);
    });
  }, []);

  return { content, ready };
}

"use client";

import VerificationLegalPageLayout from "./VerificationLegalPageLayout";
import { useTrustVerificationContent } from "./useTrustVerificationContent";
import {
  verificationTermsContent,
  verificationPrivacyContent,
  verificationRefundContent,
} from "./verificationLegalContent";

const FALLBACK_BY_SLUG = {
  terms: verificationTermsContent,
  privacy: verificationPrivacyContent,
  refund: verificationRefundContent,
};

const PATHS = {
  terms: "/verification-terms",
  privacy: "/verification-privacy",
  refund: "/verification-refund-policy",
};

export default function VerificationLegalPageFromCms({ slug = "terms" }) {
  const { content } = useTrustVerificationContent();
  const fb = FALLBACK_BY_SLUG[slug] || verificationTermsContent;
  const cms = content.legal?.[slug] || {};
  const page = {
    title: cms.title || fb.title,
    description: cms.description || fb.description,
    pagePath: PATHS[slug] || fb.pagePath,
    sections: cms.sections?.length ? cms.sections : fb.sections,
  };

  return (
    <VerificationLegalPageLayout
      title={page.title}
      description={page.description}
      pagePath={page.pagePath}
      sections={page.sections}
    />
  );
}

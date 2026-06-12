/**
 * Fallback CMS payload when /content/public is unavailable (Task 12).
 */
import {
  TV_CONSENT_TEXT,
  TV_DATA_USAGE_TEXT,
  TV_REPORT_DISCLAIMER_TEXT,
} from "./ui/trustVerificationLegalCopy";
import {
  verificationTermsContent,
  verificationPrivacyContent,
  verificationRefundContent,
} from "./verificationLegalContent";

export const TRUST_VERIFICATION_CONTENT_FALLBACK = {
  hub: {
    badge: "Sukoon Homes Trust Verification",
    hero_title: "Background verification you can trust",
    hero_subtitle:
      "Verify tenants or property owners before you sign — city-wise packages, secure documents, and PDF reports delivered by email.",
    trust_badges: [
      { icon: "🔒", label: "Secure verification" },
      { icon: "🛡️", label: "Privacy protected" },
      { icon: "✉️", label: "Report by email" },
    ],
    city_selector_helper: "Select city",
    tenant_card_title: "Tenant verification",
    tenant_card_description:
      "For landlords — verify a renter's identity and background before handing over keys.",
    owner_card_title: "Owner verification",
    owner_card_description:
      "For tenants — confirm the landlord and property are genuine before you pay token or rent.",
    starting_price_helper: "Loading pricing…",
    footer_disclaimer: TV_REPORT_DISCLAIMER_TEXT,
    empty_state_title: "Not available in your area yet",
    empty_state_description:
      "We are expanding verification services to more cities. Check back soon or contact support.",
    sample_report_title: "Sample verification report",
    sample_report_description:
      "See the kind of PDF report you receive after verification completes. This sample uses fictional data only.",
    sample_report_button_text: "View Sample Report",
  },
  wizard: {
    package_selection_intro:
      "Select a package for your city. Pricing includes GST where applicable.",
    document_upload_instructions:
      "Upload clear photos or PDFs. Accepted formats: JPG, PNG, WEBP, or PDF (max 5 MB each).",
    data_usage_notice: TV_DATA_USAGE_TEXT,
    consent_checkbox_text: TV_CONSENT_TEXT,
    review_step_disclaimer:
      "Please review details carefully. Reports are informational and do not guarantee future conduct or legal outcomes.",
    payment_step_disclaimer:
      "Online payment is processed securely via Cashfree when enabled. You may also pay offline with our team.",
    success_title: "Request received",
    success_message:
      "We will email your PDF report when verification is complete. Track progress anytime from My verification orders.",
    tenant_page_title: "Online Tenant Verification in {city}",
    tenant_page_lead:
      "Ensure safety before handing over keys — background checks with a detailed PDF report delivered by email.",
    owner_page_title: "Online Owner Verification in {city}",
    owner_page_lead:
      "Confirm the landlord is genuine before you pay token or rent — ownership and identity checks with a PDF report.",
    police_verification_title: "Rajasthan Police verification",
    police_verification_description:
      "After applying on the official Rajasthan Police citizen portal, share your police station, reference number, and upload the acknowledgement slip here.",
  },
  faq: [
    {
      question: "Who should order tenant verification?",
      answer:
        "Landlords and property owners screening a prospective renter before signing a lease or handing over keys.",
    },
    {
      question: "Who should order owner verification?",
      answer:
        "Tenants and renters who want to confirm the landlord and property details before paying token or rent.",
    },
    {
      question: "How long does the report take?",
      answer:
        "Depending on your package, reports are typically delivered within 48–72 hours by email as a PDF.",
    },
    {
      question: "Is my data secure?",
      answer:
        "We use encrypted storage for documents and reports. ID numbers are masked; full ID is not stored in plain text.",
    },
    {
      question: "Can I pay online?",
      answer:
        "Yes — Cashfree is available when enabled. You can also complete payment offline with our team.",
    },
  ],
  legal: {
    terms: verificationTermsContent,
    privacy: verificationPrivacyContent,
    refund: verificationRefundContent,
  },
  testimonials: [
    {
      customer_name: "Priya S.",
      rating: 5,
      review: "Clear report before we rented out our flat in Barmer.",
      city: "Barmer",
    },
    {
      customer_name: "Rahul M.",
      rating: 5,
      review: "Owner verification gave us confidence before paying token.",
      city: "Barmer",
    },
  ],
  report: {
    disclaimer: TV_REPORT_DISCLAIMER_TEXT,
    risk_summary_default:
      "This summary is based on checks completed at the time of verification. It is not a guarantee of future behaviour.",
    footer: "Sukoon Homes Trust Verification — Barmer",
    rating_green: "Green: No significant concerns identified in completed checks.",
    rating_amber: "Amber: Minor discrepancies or incomplete data — review details before deciding.",
    rating_red: "Red: Material concerns found — we recommend additional due diligence.",
    police_verification_disclaimer:
      "Manual verification record maintained by Sukoon Homes. Rajasthan Police status is tracked separately on the official citizen portal.",
  },
};

<?php

namespace App\Plugins\TrustVerification\Services;

use App\Plugins\TrustVerification\Models\TvContentBlock;

class TrustVerificationContentDefaults
{
    /** @return list<array<string, mixed>> */
    public static function blocks(): array
    {
        $faq = [
            ['q' => 'Who should order tenant verification?', 'a' => 'Landlords and property owners screening a prospective renter before signing a lease or handing over keys.'],
            ['q' => 'Who should order owner verification?', 'a' => 'Tenants and renters who want to confirm the landlord and property details before paying token or rent.'],
            ['q' => 'How long does the report take?', 'a' => 'Depending on your package, reports are typically delivered within 48–72 hours by email as a PDF.'],
            ['q' => 'Is my data secure?', 'a' => 'We use encrypted storage for documents and reports. ID numbers are masked; full ID is not stored in plain text.'],
            ['q' => 'Can I pay online?', 'a' => 'Yes — Cashfree is available when enabled. You can also complete payment offline with our team.'],
        ];

        $blocks = [
            // Hub
            ['content_key' => 'hub.badge', 'group_key' => 'hub', 'title' => 'Hub badge', 'type' => 'text', 'content' => 'Sukoon Homes Trust Verification', 'sort_order' => 1],
            ['content_key' => 'hub.hero_title', 'group_key' => 'hub', 'title' => 'Hero title', 'type' => 'text', 'content' => 'Background verification you can trust', 'sort_order' => 2],
            ['content_key' => 'hub.hero_subtitle', 'group_key' => 'hub', 'title' => 'Hero subtitle', 'type' => 'text', 'content' => 'Verify tenants or property owners before you sign — city-wise packages, secure documents, and PDF reports delivered by email.', 'sort_order' => 3],
            ['content_key' => 'hub.trust_badges', 'group_key' => 'hub', 'title' => 'Trust badges', 'type' => 'json', 'content_json' => [
                ['icon' => '🔒', 'label' => 'Secure verification'],
                ['icon' => '🛡️', 'label' => 'Privacy protected'],
                ['icon' => '✉️', 'label' => 'Report by email'],
            ], 'sort_order' => 4],
            ['content_key' => 'hub.city_selector_helper', 'group_key' => 'hub', 'title' => 'City selector helper', 'type' => 'text', 'content' => 'Select city', 'sort_order' => 5],
            ['content_key' => 'hub.tenant_card_title', 'group_key' => 'hub', 'title' => 'Tenant card title', 'type' => 'text', 'content' => 'Tenant verification', 'sort_order' => 6],
            ['content_key' => 'hub.tenant_card_description', 'group_key' => 'hub', 'title' => 'Tenant card description', 'type' => 'text', 'content' => "For landlords — verify a renter's identity and background before handing over keys.", 'sort_order' => 7],
            ['content_key' => 'hub.owner_card_title', 'group_key' => 'hub', 'title' => 'Owner card title', 'type' => 'text', 'content' => 'Owner verification', 'sort_order' => 8],
            ['content_key' => 'hub.owner_card_description', 'group_key' => 'hub', 'title' => 'Owner card description', 'type' => 'text', 'content' => 'For tenants — confirm the landlord and property are genuine before you pay token or rent.', 'sort_order' => 9],
            ['content_key' => 'hub.starting_price_helper', 'group_key' => 'hub', 'title' => 'Starting price helper', 'type' => 'text', 'content' => 'Loading pricing…', 'sort_order' => 10],
            ['content_key' => 'hub.footer_disclaimer', 'group_key' => 'hub', 'title' => 'Footer disclaimer', 'type' => 'text', 'content' => 'Verification reports are informational and do not guarantee future conduct, legal status, or transaction safety.', 'sort_order' => 11],
            ['content_key' => 'hub.empty_state_title', 'group_key' => 'hub', 'title' => 'Empty state title', 'type' => 'text', 'content' => 'Not available in your area yet', 'sort_order' => 12],
            ['content_key' => 'hub.empty_state_description', 'group_key' => 'hub', 'title' => 'Empty state description', 'type' => 'text', 'content' => 'We are expanding verification services to more cities. Check back soon or contact support.', 'sort_order' => 13],
            ['content_key' => 'hub.sample_report_title', 'group_key' => 'hub', 'title' => 'Sample report modal title', 'type' => 'text', 'content' => 'Sample verification report', 'sort_order' => 14],
            ['content_key' => 'hub.sample_report_description', 'group_key' => 'hub', 'title' => 'Sample report modal description', 'type' => 'text', 'content' => 'See the kind of PDF report you receive after verification completes. This sample uses fictional data only.', 'sort_order' => 15],
            ['content_key' => 'hub.sample_report_button_text', 'group_key' => 'hub', 'title' => 'Sample report button text', 'type' => 'text', 'content' => 'View Sample Report', 'sort_order' => 16],

            // Wizard
            ['content_key' => 'wizard.package_selection_intro', 'group_key' => 'wizard', 'title' => 'Package selection intro', 'type' => 'text', 'content' => 'Select a package for your city. Pricing includes GST where applicable.', 'sort_order' => 1],
            ['content_key' => 'wizard.document_upload_instructions', 'group_key' => 'wizard', 'title' => 'Document upload instructions', 'type' => 'text', 'content' => 'Upload clear photos or PDFs. Accepted formats: JPG, PNG, WEBP, or PDF (max 5 MB each).', 'sort_order' => 2],
            ['content_key' => 'wizard.data_usage_notice', 'group_key' => 'wizard', 'title' => 'Data usage notice', 'type' => 'text', 'content' => 'Your documents are used only for verification. Sukoon Homes stores them securely and may share them with verification partners only for this request.', 'sort_order' => 3],
            ['content_key' => 'wizard.consent_checkbox_text', 'group_key' => 'wizard', 'title' => 'Consent checkbox text', 'type' => 'text', 'content' => TrustVerificationConsentService::CONSENT_TEXT, 'sort_order' => 4],
            ['content_key' => 'wizard.review_step_disclaimer', 'group_key' => 'wizard', 'title' => 'Review step disclaimer', 'type' => 'text', 'content' => 'Please review details carefully. Reports are informational and do not guarantee future conduct or legal outcomes.', 'sort_order' => 5],
            ['content_key' => 'wizard.payment_step_disclaimer', 'group_key' => 'wizard', 'title' => 'Payment step disclaimer', 'type' => 'text', 'content' => 'Online payment is processed securely via Cashfree when enabled. You may also pay offline with our team.', 'sort_order' => 6],
            ['content_key' => 'wizard.success_title', 'group_key' => 'wizard', 'title' => 'Success page title', 'type' => 'text', 'content' => 'Request received', 'sort_order' => 7],
            ['content_key' => 'wizard.success_message', 'group_key' => 'wizard', 'title' => 'Success page message', 'type' => 'text', 'content' => 'We will email your PDF report when verification is complete. Track progress anytime from My verification orders.', 'sort_order' => 8],
            ['content_key' => 'wizard.police_verification_title', 'group_key' => 'wizard', 'title' => 'Police verification title', 'type' => 'text', 'content' => 'Rajasthan Police verification', 'sort_order' => 13],
            ['content_key' => 'wizard.police_verification_description', 'group_key' => 'wizard', 'title' => 'Police verification description', 'type' => 'text', 'content' => 'After applying on the official Rajasthan Police citizen portal, share your police station, reference number, and upload the acknowledgement slip here.', 'sort_order' => 14],
            ['content_key' => 'wizard.tenant_page_title', 'group_key' => 'wizard', 'title' => 'Tenant wizard title template', 'type' => 'text', 'content' => 'Online Tenant Verification in {city}', 'sort_order' => 9],
            ['content_key' => 'wizard.tenant_page_lead', 'group_key' => 'wizard', 'title' => 'Tenant wizard lead', 'type' => 'text', 'content' => 'Ensure safety before handing over keys — background checks with a detailed PDF report delivered by email.', 'sort_order' => 10],
            ['content_key' => 'wizard.owner_page_title', 'group_key' => 'wizard', 'title' => 'Owner wizard title template', 'type' => 'text', 'content' => 'Online Owner Verification in {city}', 'sort_order' => 11],
            ['content_key' => 'wizard.owner_page_lead', 'group_key' => 'wizard', 'title' => 'Owner wizard lead', 'type' => 'text', 'content' => 'Confirm the landlord is genuine before you pay token or rent — ownership and identity checks with a PDF report.', 'sort_order' => 12],

            // Report templates
            ['content_key' => 'report.disclaimer', 'group_key' => 'report', 'title' => 'PDF report disclaimer', 'type' => 'report', 'content' => 'Verification reports are informational and do not guarantee future conduct, legal status, or transaction safety.', 'sort_order' => 1],
            ['content_key' => 'report.risk_summary_default', 'group_key' => 'report', 'title' => 'Risk summary default', 'type' => 'report', 'content' => 'This summary is based on checks completed at the time of verification. It is not a guarantee of future behaviour.', 'sort_order' => 2],
            ['content_key' => 'report.footer', 'group_key' => 'report', 'title' => 'Report footer', 'type' => 'report', 'content' => 'Sukoon Homes Trust Verification — Barmer', 'sort_order' => 3],
            ['content_key' => 'report.rating_green', 'group_key' => 'report', 'title' => 'Green rating explanation', 'type' => 'report', 'content' => 'Green: No significant concerns identified in completed checks.', 'sort_order' => 4],
            ['content_key' => 'report.rating_amber', 'group_key' => 'report', 'title' => 'Amber rating explanation', 'type' => 'report', 'content' => 'Amber: Minor discrepancies or incomplete data — review details before deciding.', 'sort_order' => 5],
            ['content_key' => 'report.rating_red', 'group_key' => 'report', 'title' => 'Red rating explanation', 'type' => 'report', 'content' => 'Red: Material concerns found — we recommend additional due diligence.', 'sort_order' => 6],
            ['content_key' => 'report.police_verification_disclaimer', 'group_key' => 'report', 'title' => 'Police verification disclaimer', 'type' => 'report', 'content' => 'Manual verification record maintained by Sukoon Homes. Rajasthan Police status is tracked separately on the official citizen portal.', 'sort_order' => 7],

            // Email templates
            ['content_key' => 'email.order_submitted', 'group_key' => 'email', 'title' => 'Order submitted email', 'type' => 'email', 'content_json' => [
                'subject' => 'Verification request received — {order_number}',
                'body_html' => '<p>We received your Sukoon Homes verification request.</p><p>Track status from My verification orders.</p>',
                'body_text' => 'We received your Sukoon Homes verification request.',
            ], 'sort_order' => 1],
            ['content_key' => 'email.payment_received', 'group_key' => 'email', 'title' => 'Payment received email', 'type' => 'email', 'content_json' => [
                'subject' => 'Payment confirmed — {order_number}',
                'body_html' => '<p>Your payment for verification order {order_number} is confirmed. Our team will begin checks shortly.</p>',
                'body_text' => 'Payment confirmed for order {order_number}.',
            ], 'sort_order' => 2, 'is_active' => false],
            ['content_key' => 'email.report_ready', 'group_key' => 'email', 'title' => 'Report ready email', 'type' => 'email', 'content_json' => [
                'subject' => 'Verification report ready — {order_number}',
                'body_html' => '<p>Your verification report is ready. Download it from My verification orders.</p>',
                'body_text' => 'Your verification report is ready.',
            ], 'sort_order' => 3],
            ['content_key' => 'email.cancellation', 'group_key' => 'email', 'title' => 'Cancellation email', 'type' => 'email', 'content_json' => [
                'subject' => 'Verification order cancelled — {order_number}',
                'body_html' => '<p>Your verification order {order_number} has been cancelled.</p>',
                'body_text' => 'Order {order_number} cancelled.',
            ], 'sort_order' => 4, 'is_active' => false],

            // Legal pages (JSON structure matches frontend)
            ['content_key' => 'legal.terms', 'group_key' => 'legal', 'title' => 'Verification Terms', 'type' => 'legal', 'content_json' => self::termsLegal(), 'sort_order' => 1],
            ['content_key' => 'legal.privacy', 'group_key' => 'legal', 'title' => 'Privacy Notice', 'type' => 'legal', 'content_json' => self::privacyLegal(), 'sort_order' => 2],
            ['content_key' => 'legal.refund', 'group_key' => 'legal', 'title' => 'Refund Policy', 'type' => 'legal', 'content_json' => self::refundLegal(), 'sort_order' => 3],
        ];

        $i = 1;
        foreach ($faq as $item) {
            $blocks[] = [
                'content_key' => 'faq.'.$i,
                'group_key' => 'faq',
                'title' => 'FAQ #'.$i,
                'type' => TvContentBlock::TYPE_FAQ,
                'content_json' => ['question' => $item['q'], 'answer' => $item['a']],
                'sort_order' => $i,
            ];
            $i++;
        }

        $testimonials = [
            ['customer_name' => 'Priya S.', 'rating' => 5, 'review' => 'Clear report before we rented out our flat in Barmer.', 'city' => 'Barmer'],
            ['customer_name' => 'Rahul M.', 'rating' => 5, 'review' => 'Owner verification gave us confidence before paying token.', 'city' => 'Barmer'],
        ];
        $i = 1;
        foreach ($testimonials as $t) {
            $blocks[] = [
                'content_key' => 'testimonial.'.$i,
                'group_key' => 'testimonials',
                'title' => 'Testimonial #'.$i,
                'type' => TvContentBlock::TYPE_TESTIMONIAL,
                'content_json' => $t,
                'sort_order' => $i,
            ];
            $i++;
        }

        return $blocks;
    }

    /** @return array<string, mixed> */
    public static function termsLegal(): array
    {
        return [
            'title' => 'Sukoon Homes Verification Terms',
            'description' => 'Terms for tenant and owner background verification orders placed through Sukoon Homes.',
            'sections' => [
                ['heading' => 'What verification means', 'paragraphs' => ['Sukoon Homes Trust Verification offers background checks for rental decisions. Tenant verification helps landlords screen prospective renters. Owner verification helps tenants confirm landlord and property details before paying token or rent.']],
                ['heading' => 'Informational reports only', 'bullets' => ['Verification reports are provided for your information to support rental decisions.', 'Reports do not guarantee future behaviour, legal status, character, or transaction safety.', 'Sukoon Homes does not provide legal advice. You remain responsible for your own due diligence and agreements.']],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function privacyLegal(): array
    {
        return [
            'title' => 'Trust Verification Privacy Notice',
            'description' => 'How Sukoon Homes collects, uses, and protects personal data for background verification requests.',
            'sections' => [
                ['heading' => 'What data we collect', 'bullets' => ['Subject details: name, phone, email, addresses, ID type and number (stored with masking where applicable).', 'Documents you upload for verification purposes only.']],
                ['heading' => 'Contact and support', 'paragraphs' => ['For privacy questions, contact Sukoon Homes support at sitigroup.co@gmail.com or +91 85948 82948.']],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function refundLegal(): array
    {
        return [
            'title' => 'Trust Verification Refund Policy',
            'description' => 'Refund rules for Sukoon Homes Trust Verification orders.',
            'sections' => [
                ['heading' => 'Before verification starts', 'bullets' => ['You may request a refund if verification work has not yet started.']],
                ['heading' => 'How to contact support', 'paragraphs' => ['Email sitigroup.co@gmail.com or call +91 85948 82948 with your order number.']],
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public static function findDefault(string $contentKey): ?array
    {
        foreach (self::blocks() as $block) {
            if ($block['content_key'] === $contentKey) {
                return $block;
            }
        }

        return null;
    }
}

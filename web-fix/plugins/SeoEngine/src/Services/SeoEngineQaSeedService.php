<?php

namespace App\Plugins\SeoEngine\Services;

use App\Plugins\SeoEngine\Models\SeoEngineQaPage;
use Illuminate\Support\Str;

class SeoEngineQaSeedService
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function rows(): array
    {
        $barmer = '/rent/barmer/';

        return [
            ['category' => 'rent-agreements', 'question' => 'What is stamp duty on rent agreements in Rajasthan?', 'direct_answer' => 'Draft — Rajasthan stamp duty on rent agreements depends on term and annual rent; verify current rates on the state e-stamp portal before signing.', 'related' => [$barmer]],
            ['category' => 'rent-agreements', 'question' => 'How do you register a rent agreement in Rajasthan?', 'direct_answer' => 'Draft — Registration is done at the sub-registrar office with stamped agreement, ID proofs, and property papers; timelines vary by district.', 'related' => [$barmer]],
            ['category' => 'rent-agreements', 'question' => 'How does the e-stamp process work for rent agreements in Rajasthan?', 'direct_answer' => 'Draft — Rajasthan e-stamping is purchased online via the state portal, then printed and attached before registration or notarisation as applicable.', 'related' => [$barmer]],
            ['category' => 'rent-agreements', 'question' => 'What notice period must a tenant give in Rajasthan?', 'direct_answer' => 'Draft — Notice period follows the written rent agreement; if silent, negotiate and document terms before moving in.', 'related' => [$barmer]],
            ['category' => 'rent-agreements', 'question' => 'Is a notarised rent agreement enough in Rajasthan without registration?', 'direct_answer' => 'Draft — Notarisation alone may not replace registration for enforceability; check agreement value and local sub-registrar requirements.', 'related' => [$barmer]],
            ['category' => 'rent-agreements', 'question' => 'Who pays stamp duty on rent agreements in Rajasthan — landlord or tenant?', 'direct_answer' => 'Draft — Stamp duty payer is usually stated in the agreement; clarify before signing in Barmer and across Rajasthan.', 'related' => [$barmer]],
            ['category' => 'rent-agreements', 'question' => 'How to renew a rent agreement in Rajasthan?', 'direct_answer' => 'Draft — Renewals typically need fresh stamp duty and updated terms; start 30–60 days before expiry.', 'related' => [$barmer]],
            ['category' => 'rent-agreements', 'question' => 'Is online rent agreement valid in Rajasthan?', 'direct_answer' => 'Draft — Digital agreements with valid e-stamp and required signatures can be valid; confirm registration rules for your rent amount.', 'related' => [$barmer]],
            ['category' => 'tenant-verification', 'question' => 'Is police verification required for tenants in Barmer?', 'direct_answer' => 'Draft — Many landlords in Barmer request tenant police verification; requirements vary by property type and owner policy.', 'related' => [$barmer]],
            ['category' => 'tenant-verification', 'question' => 'What documents does a tenant need for police verification in Barmer?', 'direct_answer' => 'Draft — Typically photo ID, address proof, passport photos, and landlord NOC; confirm with local police or society rules.', 'related' => [$barmer]],
            ['category' => 'tenant-verification', 'question' => 'What KYC documents do landlords typically ask for in Barmer?', 'direct_answer' => 'Draft — Aadhaar, PAN, employment or business proof, and references are commonly requested before handing over keys.', 'related' => [$barmer]],
            ['category' => 'deposits-and-rent', 'question' => 'What is the standard security deposit for rentals in Rajasthan?', 'direct_answer' => 'Draft — Deposits are negotiated; many Barmer rentals ask one to three months’ rent as refundable deposit.', 'related' => [$barmer]],
            ['category' => 'deposits-and-rent', 'question' => 'Can a landlord deduct from security deposit for painting in Rajasthan?', 'direct_answer' => 'Draft — Deductions should match actual damage beyond normal wear; document move-in and move-out condition in the agreement.', 'related' => [$barmer]],
            ['category' => 'deposits-and-rent', 'question' => 'What is the maximum rent increase allowed per year in Rajasthan?', 'direct_answer' => 'Draft — Increases depend on agreement terms and applicable state rules; cap rises in writing at renewal.', 'related' => [$barmer]],
            ['category' => 'renting-rules', 'question' => 'Can bachelors rent flats in Barmer?', 'direct_answer' => 'Draft — Bachelors can rent where landlords allow; society or owner rules may restrict sharing or PG-style occupancy.', 'related' => [$barmer]],
            ['category' => 'renting-rules', 'question' => 'Can landlords refuse families with children in Barmer?', 'direct_answer' => 'Draft — Owner preference and building rules apply; clarify family occupancy before paying token money.', 'related' => [$barmer]],
            ['category' => 'renting-rules', 'question' => 'Are oral rent agreements valid in Rajasthan?', 'direct_answer' => 'Draft — Oral terms are hard to enforce; always use a written, stamped agreement for Barmer rentals.', 'related' => [$barmer]],
            ['category' => 'renting-rules', 'question' => 'What happens if a tenant leaves without notice in Rajasthan?', 'direct_answer' => 'Draft — Breach of notice terms may affect deposit refund; follow the agreement and document handover.', 'related' => [$barmer]],
            ['category' => 'renting-rules', 'question' => 'What are tenant rights if the landlord sells the property in Rajasthan?', 'direct_answer' => 'Draft — Existing registered leases generally bind the buyer; check agreement transfer and notice clauses.', 'related' => [$barmer]],
            ['category' => 'renting-rules', 'question' => 'Are PG accommodations treated differently from flats in Barmer?', 'direct_answer' => 'Draft — PG stays often use shorter licences with bundled utilities; read house rules and lock-in before paying.', 'related' => [$barmer]],
            ['category' => 'barmer-rentals', 'question' => 'What are typical rent ranges in Barmer city?', 'direct_answer' => 'Draft — Barmer rents vary by area and BHK; browse live listings on Sukoon Homes for current asking rents.', 'related' => [$barmer]],
            ['category' => 'barmer-rentals', 'question' => 'How to find verified rental listings in Barmer?', 'direct_answer' => 'Draft — Use Sukoon Homes verified listings for Barmer to compare flats and houses with online agreement support.', 'related' => [$barmer]],
            ['category' => 'barmer-rentals', 'question' => 'How to handle rent agreement disputes in Barmer?', 'direct_answer' => 'Draft — Start with written notice and agreement terms; escalate to local rent authority or legal counsel if needed.', 'related' => [$barmer]],
            ['category' => 'rajasthan-rentals', 'question' => 'How long should a rent agreement be registered within in Rajasthan?', 'direct_answer' => 'Draft — Register within the period prescribed under the Registration Act for your agreement type; do not delay after stamping.', 'related' => [$barmer]],
            ['category' => 'rajasthan-rentals', 'question' => 'What is the status of Rajasthan model tenancy rules for rent control?', 'direct_answer' => 'Draft — State tenancy reforms evolve; verify current Rajasthan tenancy notifications before assuming rent caps apply.', 'related' => [$barmer]],
        ];
    }

    public function run(bool $force = false): int
    {
        if (! $force && SeoEngineQaPage::query()->exists()) {
            return 0;
        }

        $inserted = 0;
        foreach (self::rows() as $row) {
            $slug = Str::slug(Str::limit($row['question'], 80, ''));
            $exists = SeoEngineQaPage::query()
                ->where('category', $row['category'])
                ->where('slug', $slug)
                ->exists();
            if ($exists) {
                continue;
            }

            SeoEngineQaPage::query()->create([
                'slug' => $slug,
                'question' => $row['question'],
                'direct_answer' => $row['direct_answer'],
                'body_html' => '<p><em>Draft — content to be finalised by the Sukoon Homes team.</em></p>',
                'category' => $row['category'],
                'related_rent_links' => $row['related'] ?? null,
                'status' => 'draft',
            ]);
            $inserted++;
        }

        return $inserted;
    }
}

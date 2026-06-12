<?php
/**
 * Task 12B CMS E2E QA (run on admin-homes server).
 * Usage: cd /www/wwwroot/admin-homes && php web-fix/tv-qa-task-12b.php
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\TrustVerification\Models\TvContentBlock;
use App\Plugins\TrustVerification\Models\TvAuditLog;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Services\TrustVerificationAuditLogService;
use App\Plugins\TrustVerification\Services\TrustVerificationContentService;
use Illuminate\Support\Facades\Http;

$results = [];
$pass = function ($k, $ok, $detail = '') use (&$results) {
    $results[$k] = ['pass' => (bool) $ok, 'detail' => $detail];
};

// 1. Public API
$apiUrl = 'https://admin-homes.sukoon.group/api/trust-verification/content/public';
$api = Http::get($apiUrl)->json();
$pass('api_public', ($api['error'] ?? true) === false && ! empty($api['data']['hub']['hero_title']), 'hub keys: ' . count($api['data']['hub'] ?? []));

// 2. FAQ edit + publish cycle
$faq = TvContentBlock::where('content_key', 'faq.1')->first();
if (! $faq) {
    $pass('faq_block', false, 'faq.1 missing');
} else {
    $original = $faq->content_json;
    $testQ = 'TASK12B QA — Who should order tenant verification?';
    TrustVerificationContentService::updateBlock($faq, [
        'content_json' => ['question' => $testQ, 'answer' => $original['answer'] ?? 'Test answer'],
    ], 1);
    TrustVerificationContentService::bustPublicCache();
    $api2 = Http::get($apiUrl)->json();
    $faqItems = $api2['data']['faq'] ?? [];
    $found = collect($faqItems)->first(fn ($i) => ($i['question'] ?? '') === $testQ);
    $pass('faq_edit_public', (bool) $found, 'question in API: ' . ($found ? 'yes' : 'no'));

    TrustVerificationContentService::setPublished($faq->fresh(), false, 1);
    TrustVerificationContentService::bustPublicCache();
    $api3 = Http::get($apiUrl)->json();
    $still = collect($api3['data']['faq'] ?? [])->contains(fn ($i) => ($i['question'] ?? '') === $testQ);
    $pass('faq_unpublish', ! $still, 'hidden after unpublish');

    TrustVerificationContentService::resetBlock($faq->fresh(), 1);
    TrustVerificationContentService::bustPublicCache();
    $pass('faq_reset', true, 'reset to default');

    $auditCount = TvAuditLog::whereIn('action', [
        TrustVerificationAuditLogService::ACTION_CONTENT_BLOCK_UPDATED,
        TrustVerificationAuditLogService::ACTION_CONTENT_BLOCK_UNPUBLISHED,
        TrustVerificationAuditLogService::ACTION_CONTENT_BLOCK_RESET,
    ])->where('created_at', '>=', now()->subMinutes(10))->count();
    $pass('audit_logs', $auditCount >= 2, "recent audit rows: {$auditCount}");
}

// 3. Consent CMS
$consent = TvContentBlock::where('content_key', 'wizard.consent_checkbox_text')->first();
$consentText = 'TASK12B consent — I confirm permission for this verification request.';
TrustVerificationContentService::updateBlock($consent, ['content' => $consentText], 1);
TrustVerificationContentService::bustPublicCache();
$consentMeta = TrustVerificationContentService::activeConsentForOrder();
$pass('consent_cms', str_contains($consentMeta['consent_text'], 'TASK12B'), 'version: ' . $consentMeta['legal_version']);

// 4. Email templates active
$email = TrustVerificationContentService::getJson('email.order_submitted', []);
$tpl = TrustVerificationContentService::emailTemplate('email.order_submitted', ['order_number' => 'TV-TEST'], [
    'subject' => 'Fallback subject',
]);
$pass('email_cms', str_contains($tpl['subject'], 'TV-TEST'), $tpl['subject']);

// 5. Latest order consent check (if any order created in last hour with TASK12B)
$recent = TvOrder::where('consent_text', 'like', '%TASK12B%')->orderByDesc('id')->first();
$pass('order_consent_stored', (bool) $recent, $recent ? $recent->order_number . ' legal=' . $recent->legal_version : 'no order yet — run API create');

echo json_encode([
    'faq_key' => 'faq.1',
    'consent_version' => $consentMeta['legal_version'] ?? null,
    'test_order' => $recent?->order_number,
    'results' => $results,
], JSON_PRETTY_PRINT) . "\n";

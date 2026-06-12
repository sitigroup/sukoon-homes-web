<?php
/**
 * Restore faq.1 + wizard.consent_checkbox_text to production defaults.
 * Usage: cd /www/wwwroot/admin-homes && php web-fix/tv-restore-production-cms.php
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\TrustVerification\Models\TvContentBlock;
use App\Plugins\TrustVerification\Services\TrustVerificationConsentService;
use App\Plugins\TrustVerification\Services\TrustVerificationContentService;

$keys = ['faq.1', 'wizard.consent_checkbox_text'];
$out = [];

foreach ($keys as $key) {
    $block = TvContentBlock::where('content_key', $key)->first();
    if (! $block) {
        $out[$key] = ['error' => 'block not found'];
        continue;
    }

    $before = $key === 'faq.1'
        ? ($block->content_json['question'] ?? '')
        : $block->content;

    TrustVerificationContentService::resetBlock($block, 1);
    $block = $block->fresh();

    $after = $key === 'faq.1'
        ? ($block->content_json['question'] ?? '')
        : $block->content;

    $out[$key] = [
        'before' => $before,
        'after' => $after,
        'version' => $block->version,
        'published' => $block->is_active,
    ];
}

TrustVerificationContentService::bustPublicCache();

$out['expected_consent'] = TrustVerificationConsentService::CONSENT_TEXT;
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";

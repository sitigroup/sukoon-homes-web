<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$b = App\Plugins\TrustVerification\Models\TvContentBlock::where('content_key', 'faq.1')->first();
App\Plugins\TrustVerification\Services\TrustVerificationContentService::updateBlock($b, [
    'content_json' => [
        'question' => 'TASK12B FAQ visible on site?',
        'answer' => 'Yes — this FAQ was loaded from the CMS public API.',
    ],
], 1);
App\Plugins\TrustVerification\Services\TrustVerificationContentService::setPublished($b->fresh(), true, 1);
App\Plugins\TrustVerification\Services\TrustVerificationContentService::bustPublicCache();
echo "ok\n";

<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\TrustVerification\Models\TvAuditLog;
use App\Plugins\TrustVerification\Models\TvContentBlock;
use App\Plugins\TrustVerification\Services\TrustVerificationAuditLogService;
use App\Plugins\TrustVerification\Support\TrustVerificationContentPreview;
use Illuminate\Support\Carbon;

try {
    $tab = 'hub';
    $blocks = TvContentBlock::query()->where('group_key', $tab)->orderBy('sort_order')->limit(3)->get();
    $lastUpdated = TvContentBlock::query()->max('updated_at');
    $stats = [
        'total' => TvContentBlock::query()->count(),
        'published' => TvContentBlock::query()->where('is_active', true)->count(),
        'draft' => TvContentBlock::query()->where('is_active', false)->count(),
        'last_updated' => $lastUpdated ? Carbon::parse($lastUpdated) : null,
    ];
    $contentActions = [
        TrustVerificationAuditLogService::ACTION_CONTENT_BLOCK_UPDATED,
        TrustVerificationAuditLogService::ACTION_CONTENT_BLOCK_PUBLISHED,
        TrustVerificationAuditLogService::ACTION_CONTENT_BLOCK_UNPUBLISHED,
        TrustVerificationAuditLogService::ACTION_CONTENT_BLOCK_RESET,
    ];
    $recentEdits = TvAuditLog::query()->whereIn('action', $contentActions)->latest('id')->limit(8)->get();
    $mostEdited = TvAuditLog::query()->whereIn('action', $contentActions)->latest('id')->limit(200)->get()
        ->groupBy(fn (TvAuditLog $log) => (string) ($log->metadata['content_key'] ?? 'unknown'))
        ->map->count()->sortDesc()->take(5);
    $blocksWithPreview = $blocks->map(fn (TvContentBlock $block) => [
        'block' => $block,
        'headline' => TrustVerificationContentPreview::headline($block),
        'snippet' => TrustVerificationContentPreview::snippet($block),
    ]);
    $html = view('trust-verification::admin.content.index', [
        'tab' => $tab,
        'tabs' => ['hub', 'wizard', 'faq', 'legal', 'email', 'report', 'testimonials'],
        'tabMeta' => [
            'hub' => ['label' => 'Hub', 'icon' => 'bi-house-door'],
        ],
        'blocks' => $blocks,
        'blocksWithPreview' => $blocksWithPreview,
        'stats' => $stats,
        'filters' => ['q' => '', 'status' => 'all', 'recent' => false],
        'recentEdits' => $recentEdits,
        'mostEdited' => $mostEdited,
        'sortable' => false,
        'tvPermissions' => ['read' => true, 'settings' => true],
    ])->render();
    echo "View render OK, length=".strlen($html)."\n";
} catch (Throwable $e) {
    echo "ERROR: ".$e->getMessage()."\n".$e->getFile().':'.$e->getLine()."\n";
}

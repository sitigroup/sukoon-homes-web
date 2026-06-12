<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\TrustVerification\Models\TvAuditLog;
use App\Plugins\TrustVerification\Services\TrustVerificationAuditLogService;

$rows = TvAuditLog::whereIn('action', [
    TrustVerificationAuditLogService::ACTION_CONTENT_BLOCK_UPDATED,
    TrustVerificationAuditLogService::ACTION_CONTENT_BLOCK_UNPUBLISHED,
    TrustVerificationAuditLogService::ACTION_CONTENT_BLOCK_RESET,
    TrustVerificationAuditLogService::ACTION_CONTENT_BLOCK_PUBLISHED,
])->orderByDesc('id')->limit(5)->get(['id', 'action', 'description', 'created_at']);

echo json_encode(['count' => $rows->count(), 'recent' => $rows], JSON_PRETTY_PRINT);

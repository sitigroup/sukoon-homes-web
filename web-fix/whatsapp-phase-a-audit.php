<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\Whatsapp\Models\WaEventMap;
use App\Plugins\Whatsapp\Models\WaMessage;
use App\Plugins\Whatsapp\Models\WaSetting;
use App\Plugins\Whatsapp\Models\WaTemplate;
use Illuminate\Support\Facades\DB;

$s = WaSetting::query()->latest('id')->first();

$report = [
    'settings' => [
        'environment_mode' => $s?->environment_mode,
        'webhook_verified' => (bool) ($s?->webhook_verified ?? false),
        'has_access_token' => ! empty($s?->access_token),
        'has_app_secret' => ! empty($s?->app_secret),
        'has_phone_number_id' => ! empty($s?->phone_number_id),
        'has_waba_id' => ! empty($s?->waba_id),
        'has_verify_token' => ! empty($s?->verify_token),
        'last_tested_at' => $s?->last_tested_at ? (string) $s->last_tested_at : null,
    ],
    'webhook' => [
        'url' => url('/api/whatsapp/webhook'),
        'last_received_at' => DB::table('wa_audit_log')->where('event', 'webhook_received')->max('created_at'),
        'inbound_24h' => WaMessage::query()->where('direction', 'in')->where('created_at', '>=', now()->subDay())->count(),
    ],
    'delivery_7d' => [
        'out_sent' => WaMessage::query()->where('direction', 'out')->where('created_at', '>=', now()->subDays(7))->count(),
        'out_failed' => WaMessage::query()->where('direction', 'out')->where('status', 'failed')->where('created_at', '>=', now()->subDays(7))->count(),
    ],
    'events_enabled' => WaEventMap::query()->where('enabled', true)->pluck('event_key')->values()->all(),
    'templates_approved' => WaTemplate::query()->whereRaw('LOWER(COALESCE(status, "")) = ?', ['approved'])->where('enabled', true)->count(),
    'pending_a1' => [],
];

if (($s?->environment_mode ?? '') !== 'production_waba') {
    $report['pending_a1'][] = 'Switch environment to production_waba in Settings';
}
if (empty($s?->access_token)) {
    $report['pending_a1'][] = 'Save permanent Meta access token';
}
if (empty($s?->webhook_verified)) {
    $report['pending_a1'][] = 'Webhook not marked verified — run Test Connection and register URL in Meta';
}
if (empty($s?->app_secret)) {
    $report['pending_a1'][] = 'Save app secret for webhook signature validation';
}

echo json_encode($report, JSON_PRETTY_PRINT) . PHP_EOL;

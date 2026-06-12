<?php
/**
 * Apply Phase A pending ops that are safe on server (no secrets printed).
 * Run: sudo -u www php web-fix/whatsapp-phase-a-apply.php
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\Whatsapp\Models\WaEventMap;
use App\Plugins\Whatsapp\Models\WaSetting;
use App\Plugins\Whatsapp\Services\MetaGraphClient;
use App\Plugins\Whatsapp\Support\WhatsappEventCatalog;

$settings = WaSetting::query()->latest('id')->first();
if (! $settings) {
    fwrite(STDERR, "No wa_settings row.\n");
    exit(1);
}

$changes = [];

if (($settings->environment_mode ?? '') !== 'production_waba') {
    $settings->environment_mode = 'production_waba';
    $settings->save();
    $changes[] = 'environment_mode => production_waba';
}

$wiredKeys = array_keys(array_filter(
    WhatsappEventCatalog::systemDefinitions(),
    fn ($def) => (bool) ($def['wired'] ?? false)
));

foreach ($wiredKeys as $key) {
    $map = WaEventMap::query()->where('event_key', $key)->first();
    if (! $map || ! $map->template_id) {
        continue;
    }
    if (! $map->enabled) {
        $map->enabled = true;
        $map->save();
        $changes[] = "enabled event: {$key}";
    }
}

$meta = app(MetaGraphClient::class);
$test = $meta->testConnection();
if ($test['ok'] ?? false) {
    $settings->refresh();
    $settings->webhook_verified = true;
    $settings->last_tested_at = now();
    $settings->save();
    $changes[] = 'Meta test connection OK; webhook_verified refreshed';
} else {
    $changes[] = 'Meta test connection FAILED: ' . ($test['message'] ?? 'unknown');
}

echo json_encode([
    'changes' => $changes,
    'environment_mode' => $settings->fresh()->environment_mode,
    'webhook_verified' => (bool) $settings->fresh()->webhook_verified,
], JSON_PRETTY_PRINT) . PHP_EOL;

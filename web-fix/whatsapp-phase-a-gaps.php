<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\Whatsapp\Models\WaEventMap;
use App\Plugins\Whatsapp\Models\WaMessage;
use App\Plugins\Whatsapp\Support\MetaApiErrorFormatter;
use App\Plugins\Whatsapp\Support\WhatsappEventCatalog;

$wired = WhatsappEventCatalog::systemDefinitions();
$gaps = [];
foreach ($wired as $key => $def) {
    if (! ($def['wired'] ?? false)) {
        continue;
    }
    $map = WaEventMap::query()->where('event_key', $key)->first();
    if (! $map) {
        $gaps[] = ['event_key' => $key, 'issue' => 'no_event_map_row'];
        continue;
    }
    if (! $map->template_id) {
        $gaps[] = ['event_key' => $key, 'issue' => 'template_not_mapped'];
        continue;
    }
    if (! $map->enabled) {
        $gaps[] = ['event_key' => $key, 'issue' => 'event_disabled'];
    }
}

$failures = WaMessage::query()
    ->where('direction', 'out')
    ->where('status', 'failed')
    ->where('created_at', '>=', now()->subDays(7))
    ->orderByDesc('id')
    ->limit(8)
    ->get(['id', 'template_key', 'type', 'error_json', 'created_at'])
    ->map(fn ($m) => [
        'at' => (string) $m->created_at,
        'event' => $m->template_key,
        'type' => $m->type,
        'meta_error' => MetaApiErrorFormatter::summarize($m->error_json),
    ])
    ->values()
    ->all();

echo json_encode(['event_gaps' => $gaps, 'recent_failures' => $failures], JSON_PRETTY_PRINT) . PHP_EOL;

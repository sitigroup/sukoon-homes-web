<?php
/**
 * Map approved Meta templates to Sukoon system events.
 * Run: sudo -u www php web-fix/whatsapp-map-templates.php
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\Whatsapp\Models\WaEventMap;
use App\Plugins\Whatsapp\Models\WaTemplate;
use App\Plugins\Whatsapp\Support\WhatsappEventCatalog;
use Illuminate\Support\Facades\DB;

$map = [
    'agreement_ready' => 'agreement_ready',
    'agreement_renewal' => 'agreement_renewal',
    'maintenance_assigned' => 'maintenance_assigned',
    'maintenance_resolved_confirm' => 'maintenance_resolved',
    'owner_payment_request' => 'owner_payment_request',
    'rent_received' => 'rent_received',
    'rent_reminder' => 'rent_reminder',
    'vendor_job_assigned' => 'vendor_assigned',
];

$disable = ['hello_world'];

$changes = [];

foreach ($map as $metaName => $eventKey) {
    $template = WaTemplate::query()
        ->where('meta_template_name', $metaName)
        ->where('language', 'en')
        ->first();

    if (! $template) {
        $changes[] = "SKIP missing template: {$metaName}";

        continue;
    }

    $def = WhatsappEventCatalog::systemDefinitions()[$eventKey] ?? null;
    $existing = WaEventMap::query()->where('event_key', $eventKey)->first();

    WaEventMap::query()->updateOrCreate(
        ['event_key' => $eventKey],
        [
            'template_id' => $template->id,
            'enabled' => true,
            'language' => 'en',
            'display_name' => $existing?->display_name ?? ($def['display_name'] ?? $eventKey),
            'description' => $existing?->description ?? ($def['description'] ?? null),
            'event_type' => 'system',
        ]
    );

    $template->internal_key = $eventKey;
    $template->enabled = true;
    $template->save();

    $changes[] = "MAPPED {$metaName} -> {$eventKey} (template #{$template->id})";
}

foreach ($disable as $metaName) {
    $template = WaTemplate::query()->where('meta_template_name', $metaName)->first();
    if ($template) {
        $template->enabled = false;
        $template->internal_key = null;
        $template->save();
        $changes[] = "DISABLED {$metaName}";
    }
}

DB::table('wa_audit_log')->insert([
    'actor_id' => null,
    'event' => 'templates_bulk_mapped',
    'entity_type' => 'template',
    'meta_json' => json_encode(['changes' => $changes]),
    'created_at' => now(),
    'updated_at' => now(),
]);

$enabled = WaEventMap::query()->where('enabled', true)->pluck('event_key')->sort()->values()->all();

echo json_encode([
    'changes' => $changes,
    'enabled_events' => $enabled,
], JSON_PRETTY_PRINT) . PHP_EOL;

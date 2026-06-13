<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\SeoEngine\Models\SeoEngineLead;
use App\Plugins\SeoEngine\Services\SeoEngineSettingsService;

$lead = SeoEngineLead::latest()->first();
echo "leads_count=" . SeoEngineLead::count() . "\n";
if ($lead) {
    echo "latest_lead={$lead->id}|{$lead->name}|{$lead->source_path}|{$lead->status}\n";
}

$settings = app(SeoEngineSettingsService::class)->all();
$keys = ['lead_notify_phone', 'ga4_enabled', 'ga4_measurement_id', 'gsc_property', 'gsc_client_id'];
foreach ($keys as $k) {
    $v = $settings[$k] ?? '(unset)';
    if ($k === 'gsc_client_id' && $v && $v !== '(unset)') {
        $v = substr((string) $v, 0, 12) . '…';
    }
    echo "{$k}={$v}\n";
}

$html = file_get_contents('https://homes.sukoon.group/rent/barmer/');
preg_match('/<script id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/s', $html, $m);
if ($m) {
    $data = json_decode($m[1], true);
    $sd = $data['props']['pageProps']['structuredData'] ?? null;
    echo 'structured_data=' . ($sd ? 'yes' : 'no') . "\n";
    if ($sd) {
        $types = [];
        $walk = function ($node) use (&$walk, &$types) {
            if (! is_array($node)) {
                return;
            }
            if (isset($node['@type'])) {
                $types[] = is_array($node['@type']) ? implode(',', $node['@type']) : $node['@type'];
            }
            foreach ($node as $v) {
                if (is_array($v)) {
                    $walk($v);
                }
            }
        };
        $walk($sd);
        echo 'schema_types=' . implode(',', array_unique($types)) . "\n";
    }
} else {
    echo "structured_data=__NEXT_DATA__ missing\n";
}

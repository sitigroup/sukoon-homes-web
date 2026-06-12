<?php
/**
 * Task 18B QA — run on admin-homes: php /tmp/tv-qa-task-18b.php
 */
$base = 'https://admin-homes.sukoon.group/api/trust-verification';
$forbidden = [
    'risk_score', 'risk_level', 'fraud', 'aadhaar', 'pan', 'admin_note',
    'police_certificate', 'reference_phone', 'breakdown', 'trust_score',
    'device', 'duplicate_phone', 'internal',
];

function fetch_json(string $url): ?array {
    $ctx = stream_context_create(['http' => ['timeout' => 15]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        return null;
    }
    return json_decode($raw, true);
}

$ids = [19, 15, 1];
$pass = true;
echo "=== Task 18B API QA ===\n";

foreach ($ids as $id) {
    $url = $base . '/tenant-reliability/' . $id;
    $j = fetch_json($url);
    $code = $j === null ? 'FAIL_FETCH' : 'OK';
    echo "\nCustomer #$id ($url)\n";
    if ($j === null) {
        echo "  FETCH: FAIL\n";
        $pass = false;
        continue;
    }
    $blob = json_encode($j);
    $leaks = [];
    foreach ($forbidden as $needle) {
        if (stripos($blob, $needle) !== false) {
            $leaks[] = $needle;
        }
    }
    $data = $j['data'] ?? null;
    echo "  error flag: " . json_encode($j['error'] ?? null) . "\n";
    if ($data) {
        echo "  completion: " . ($data['verification_completion'] ?? '?') . "%\n";
        echo "  label: " . ($data['reliability_label'] ?? '?') . "\n";
        echo "  checks: " . count($data['checks'] ?? []) . "\n";
    } else {
        echo "  data: null (no tenant reliability or hidden)\n";
    }
    if ($leaks) {
        echo "  LEAK: " . implode(', ', $leaks) . "\n";
        $pass = false;
    } else {
        echo "  privacy: PASS\n";
    }
}

// Regression: public-trust still works
$trust = fetch_json($base . '/public-trust/15');
echo "\npublic-trust/15: " . ($trust && isset($trust['data']) ? 'OK' : ($trust ? 'data_null' : 'FAIL')) . "\n";
if ($trust && stripos(json_encode($trust), 'risk_score') !== false) {
    echo "  LEAK risk in public-trust\n";
    $pass = false;
}

// DB counts
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$count = \Illuminate\Support\Facades\DB::table('tv_tenant_reliability')->count();
echo "\ntv_tenant_reliability rows: $count\n";

$levels = \Illuminate\Support\Facades\DB::table('tv_tenant_reliability')
    ->select('reliability_level', \Illuminate\Support\Facades\DB::raw('count(*) as c'))
    ->groupBy('reliability_level')->get();
foreach ($levels as $row) {
    echo "  level {$row->reliability_level}: {$row->c}\n";
}

echo "\nOVERALL: " . ($pass ? 'PASS' : 'FAIL') . "\n";

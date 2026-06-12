<?php
/**
 * Re-hook listing approval + suggestion approve to resolve pending suggestions.
 * Run on server: php patch-listing-approval-resolve-suggestions.php
 */
$servicePath = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Services/AreaListingService.php';
$localService = __DIR__ . '/AreaListingService.php';

if (! is_file($localService)) {
    fwrite(STDERR, "missing local AreaListingService.php\n");
    exit(1);
}

copy($localService, $servicePath);
echo "deployed AreaListingService.php\n";

$materializeCall = "                    if (class_exists(\\App\\Plugins\\AreaListing\\Services\\AreaListingService::class)) {\n                        \\App\\Plugins\\AreaListing\\Services\\AreaListingService::materializeLocationOnListingApproval((int) \$request->id, 'LISTING_TYPE');\n                    }\n";

$hooks = [
    [
        '/www/wwwroot/admin-homes/app/Http/Controllers/ProjectController.php',
        'project',
        'if ($request->request_status == "approved") {',
    ],
    [
        '/www/wwwroot/admin-homes/app/Http/Controllers/PropertController.php',
        'property',
        'if ($request->request_status == "approved") {',
    ],
];

foreach ($hooks as [$path, $type, $search]) {
    if (! is_file($path)) {
        echo "skip missing $path\n";
        continue;
    }
    $c = file_get_contents($path);
    $needle = "materializeLocationOnListingApproval((int) \$request->id, '$type')";
    if (str_contains($c, $needle)) {
        echo "hook ok $path\n";
        continue;
    }
    $insert = str_replace('LISTING_TYPE', $type, $materializeCall);
    if (! str_contains($c, $search)) {
        echo "pattern not found $path\n";
        continue;
    }
    $c = str_replace($search, $search . "\n" . $insert, $c);
    file_put_contents($path, $c);
    echo "hooked $path\n";
}

$adminPath = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Http/Controllers/Admin/AreaListingAdminController.php';
$admin = file_get_contents($adminPath);
$propagateNeedle = 'AreaListingService::propagateApprovedSuggestion($suggestion)';
if (str_contains($admin, $propagateNeedle)) {
    echo "admin propagate hook ok\n";
} else {
    $from = "            \$this->markSuggestionReviewed(\$suggestion->id, 'approved', \$request->input('review_note'));\n        });";
    $to = "            \$this->markSuggestionReviewed(\$suggestion->id, 'approved', \$request->input('review_note'));\n            if (class_exists(\\App\\Plugins\\AreaListing\\Services\\AreaListingService::class)) {\n                \\App\\Plugins\\AreaListing\\Services\\AreaListingService::propagateApprovedSuggestion(\$suggestion);\n            }\n        });";
    if (str_contains($admin, $from)) {
        $admin = str_replace($from, $to, $admin);
        file_put_contents($adminPath, $admin);
        echo "hooked approveSuggestion propagate\n";
    } else {
        echo "WARN: approveSuggestion hook pattern not found\n";
    }
}

passthru('cd /www/wwwroot/admin-homes && php -l app/Plugins/AreaListing/Services/AreaListingService.php 2>&1');
passthru('cd /www/wwwroot/admin-homes && php -l app/Http/Controllers/ProjectController.php 2>&1');
passthru('cd /www/wwwroot/admin-homes && php -l app/Http/Controllers/PropertController.php 2>&1');
passthru('cd /www/wwwroot/admin-homes && php -l app/Plugins/AreaListing/Http/Controllers/Admin/AreaListingAdminController.php 2>&1');
passthru('cd /www/wwwroot/admin-homes && sudo -u www php artisan optimize:clear 2>&1 | tail -3');

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\AreaListing\Services\AreaListingService;

$closed = AreaListingService::resolveStalePendingSuggestions();
echo "resolved stale pending suggestions: $closed\n";

AreaListingService::materializeLocationOnListingApproval(7, 'project');
echo "materialized project 7\n";

$pending = \Illuminate\Support\Facades\DB::table('area_listing_suggestions')
    ->where('status', 'pending')
    ->whereIn('id', [53, 54])
    ->count();
echo "pending suggestions 53/54 remaining: $pending\n";

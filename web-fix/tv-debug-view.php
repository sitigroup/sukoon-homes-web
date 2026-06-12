<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$views = [
    'trust-verification::admin.content.index',
    'trust-verification::admin.content.edit',
];
foreach ($views as $v) {
    echo $v.': '.(view()->exists($v) ? 'exists' : 'MISSING')."\n";
}
$finder = view()->getFinder();
$hints = $finder->getHints();
echo "trust-verification hint: ".json_encode($hints['trust-verification'] ?? 'none')."\n";

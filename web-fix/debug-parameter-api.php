<?php
$json = file_get_contents('https://admin-homes.sukoon.group/api/get_property?id=32&nocache=' . time());
$data = json_decode($json, true);
$params = array_slice($data['data'][0]['parameters'] ?? [], 0, 5);
foreach ($params as $p) {
    echo $p['name'] . ' => ' . ($p['image'] ?? 'NULL') . PHP_EOL;
}

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$p = App\Models\parameter::find(1);
echo "Model Bedroom image accessor: " . ($p->image ?? 'NULL') . PHP_EOL;
echo "Model Bedroom raw: " . ($p->getRawOriginal('image') ?? 'NULL') . PHP_EOL;

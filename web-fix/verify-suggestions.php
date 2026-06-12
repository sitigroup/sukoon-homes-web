<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (DB::table('area_listing_suggestions')->where('status', 'pending')->orderBy('id')->limit(20)->get() as $s) {
    echo "{$s->id} {$s->type} {$s->name} area_id={$s->area_id} city={$s->city}\n";
}

echo "\nChandan:\n";
foreach (DB::table('area_listing_suggestions')->where('name', 'like', '%Chandan%')->get() as $s) {
    echo "{$s->id} {$s->status} {$s->type} {$s->name}\n";
}

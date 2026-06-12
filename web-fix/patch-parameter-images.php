<?php
/**
 * Link existing parameter SVG files to parameters table rows (Bedroom, Bathroom, etc.).
 * Run on server: php web-fix/patch-parameter-images.php
 */
$admin = getenv('ADMIN_ROOT') ?: '/www/wwwroot/admin-homes';
require $admin . '/vendor/autoload.php';
$app = require $admin . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$map = [
    1 => '1775153851-bedroom.svg',
    2 => '1775153851-bathroom.svg',
    3 => '1775153851-kitchen.svg',
    4 => '1775153851-parking.svg',
    5 => '1775153851-area.svg',
];

$storageDir = $admin . '/public/storage/parameter_img';
$updated = 0;

foreach ($map as $id => $file) {
    if (! is_file($storageDir . '/' . $file)) {
        echo "SKIP id={$id}: missing file {$file}\n";
        continue;
    }

    $changed = DB::table('parameters')
        ->where('id', $id)
        ->where(function ($q) use ($file) {
            $q->whereNull('image')->orWhere('image', '!=', $file);
        })
        ->update(['image' => $file, 'updated_at' => now()]);

    if ($changed) {
        echo "Updated parameter {$id} -> {$file}\n";
        $updated += $changed;
    }
}

echo "Done. Updated {$updated} parameter(s).\n";

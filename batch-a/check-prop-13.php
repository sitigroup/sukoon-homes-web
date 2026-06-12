<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$a = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach (['propertys', 'properties', 'property'] as $t) {
    try {
        $n = DB::table($t)->count();
        $id13 = DB::table($t)->where('id', 13)->exists();
        echo "$t: count=$n id13=" . ($id13 ? 'yes' : 'no') . PHP_EOL;
    } catch (Throwable $e) {
        echo "$t: missing" . PHP_EOL;
    }
}

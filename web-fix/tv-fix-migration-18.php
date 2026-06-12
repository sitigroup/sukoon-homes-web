<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$name = '2026_05_30_000016_create_tv_tenant_reliability_table';
$ran = DB::table('migrations')->where('migration', $name)->exists();
echo "migration_recorded: " . ($ran ? 'yes' : 'no') . "\n";

$indexes = DB::select("SHOW INDEX FROM tv_tenant_reliability WHERE Key_name = 'tv_rel_pub_completion_idx'");
echo "short_index: " . (count($indexes) ? 'yes' : 'no') . "\n";

if (! count($indexes)) {
    try {
        DB::statement('ALTER TABLE tv_tenant_reliability ADD INDEX tv_rel_pub_completion_idx (public_visible, verification_completion)');
        echo "index_added: ok\n";
    } catch (Throwable $e) {
        echo "index_added: fail " . $e->getMessage() . "\n";
    }
}

if (! $ran) {
    DB::table('migrations')->insert([
        'migration' => $name,
        'batch' => (int) DB::table('migrations')->max('batch') + 1,
    ]);
    echo "migration_marked: ok\n";
}

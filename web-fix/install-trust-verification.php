<?php
/**
 * Trust Verification v1 — PLUGIN-ONLY admin install (no wrteam core edits).
 *
 * Prerequisites:
 *   1. Fresh backup on server:
 *        bash /path/to/cursr/scripts/final-sukoon-complete-backup.sh
 *   2. Run from admin root:
 *        cd /www/wwwroot/admin-homes
 *        php /path/to/cursr/web-fix/install-trust-verification.php
 *
 * If API/admin routes 404 after install, run OPTIONAL core patch (with approval):
 *        php /path/to/cursr/web-fix/patch-trust-verification-register.php
 */
declare(strict_types=1);

$adminRoot = is_file(getcwd() . '/artisan') ? getcwd() : '/www/wwwroot/admin-homes';
$repoRoot = dirname(__DIR__);
$pluginSrc = $repoRoot . '/plugins/TrustVerification';
$pluginDest = $adminRoot . '/app/Plugins/TrustVerification';

echo "=== Trust Verification — plugin-only install ===\n";
echo "Admin root: {$adminRoot}\n\n";

if (!is_file($adminRoot . '/artisan')) {
    fwrite(STDERR, "ERROR: Run from admin-homes directory.\n");
    exit(1);
}

if (getenv('SKIP_BACKUP_CHECK') !== '1') {
    echo "STOP: Confirm you ran a fresh backup first:\n";
    echo "  bash {$repoRoot}/scripts/final-sukoon-complete-backup.sh\n\n";
    echo "If backup is done, re-run with:\n";
    echo "  SKIP_BACKUP_CHECK=1 php install-trust-verification.php\n\n";
    exit(2);
}

if (!is_dir($pluginSrc)) {
    fwrite(STDERR, "ERROR: Plugin source missing: {$pluginSrc}\n");
    exit(1);
}

echo "Copying plugin to app/Plugins/TrustVerification ...\n";
if (!is_dir($adminRoot . '/app/Plugins')) {
    mkdir($adminRoot . '/app/Plugins', 0755, true);
}
rcopy($pluginSrc, $pluginDest);

chdir($adminRoot);

echo "Running migrations ...\n";
passthru('php artisan migrate --force --path=app/Plugins/TrustVerification/database/migrations', $migrateCode);
if ($migrateCode !== 0) {
    fwrite(STDERR, "Migrate failed.\n");
    exit(1);
}

echo "Seeding Barmer packages ...\n";
passthru('php artisan db:seed --class=App\\Plugins\\TrustVerification\\Database\\Seeders\\TvPackageSeeder --force', $seedCode);
if ($seedCode !== 0) {
    fwrite(STDERR, "Seed failed — check autoload for Database/Seeders path.\n");
}

echo "Seeding cities ...\n";
passthru('php artisan db:seed --class=App\\Plugins\\TrustVerification\\Database\\Seeders\\TvCitySeeder --force', $citySeedCode);

passthru('php artisan storage:link 2>/dev/null');
passthru('php artisan optimize:clear');

echo "\n=== Route check ===\n";
passthru('php artisan route:list --path=trust-verification 2>/dev/null | head -15');

$routeCheck = shell_exec('php artisan route:list --path=trust-verification 2>/dev/null');
if (!$routeCheck || !str_contains($routeCheck, 'trust-verification')) {
    echo "\n*** Routes not registered yet ***\n";
    echo "Plugin files are installed. To enable routes (ONE optional wrteam core line), after approval run:\n";
    echo "  php {$repoRoot}/web-fix/patch-trust-verification-register.php\n";
    echo "  cd {$adminRoot} && php artisan optimize:clear\n";
}

echo "\nDone (plugin copy + migrate + seed). No core files were modified by this script.\n";

function rcopy(string $src, string $dest): void
{
    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }
    $dir = opendir($src);
    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $from = $src . '/' . $file;
        $to = $dest . '/' . $file;
        is_dir($from) ? rcopy($from, $to) : copy($from, $to);
    }
    closedir($dir);
}

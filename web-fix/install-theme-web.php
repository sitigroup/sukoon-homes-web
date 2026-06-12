<?php
/**
 * Copy Sukoon Theme Next.js plugin to homes.sukoon.group
 * Usage: php install-theme-web.php
 */
declare(strict_types=1);

$repo = dirname(__DIR__);
$src = $repo . '/web-fix/plugins/theme';
$dest = getenv('HOMES_ROOT') ?: '/www/wwwroot/homes.sukoon.group';
$target = rtrim($dest, '/') . '/src/plugins/theme';

if (! is_dir($src)) {
    fwrite(STDERR, "Source not found: {$src}\n");
    exit(1);
}

if (! is_dir(dirname($target))) {
    fwrite(STDERR, "HOMES src/plugins missing — set HOMES_ROOT or deploy to server.\n");
    exit(1);
}

function copyDir(string $from, string $to): void
{
    if (! is_dir($to)) {
        mkdir($to, 0755, true);
    }
    foreach (scandir($from) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $a = $from . '/' . $item;
        $b = $to . '/' . $item;
        if (is_dir($a)) {
            copyDir($a, $b);
        } else {
            copy($a, $b);
        }
    }
}

copyDir($src, $target);
echo "Copied theme plugin to {$target}\n";
echo "Next: node web-fix/patch-theme-app.js && node web-fix/patch-theme-layout.js\n";
echo "Then: npm run build && pm2 restart homes-sukoon\n";

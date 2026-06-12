<?php
/**
 * Deploy missing i18n keys for homepage KYC CTA and area/sub-area search labels.
 * Run: php web-fix/patch-homepage-i18n-keys.php
 */
$homes = getenv('HOMES_ROOT') ?: '/www/wwwroot/homes.sukoon.group';
$webFix = __DIR__;

foreach (['en.json', 'hi.json', 'TranslationContext.jsx'] as $file) {
    $src = $webFix . '/homes-frontend/' . $file;
    $destDir = $file === 'TranslationContext.jsx'
        ? $homes . '/src/components/context'
        : $homes . '/src/utils';
    $dest = $destDir . '/' . $file;
    if (! is_file($src)) {
        fwrite(STDERR, "Missing: {$src}\n");
        exit(1);
    }
    copy($src, $dest);
    echo "Copied {$file}\n";
}

passthru('cd ' . escapeshellarg($homes) . ' && npm run build 2>&1 | tail -8');
passthru('pm2 restart homes-sukoon 2>&1 | tail -2');
echo "Done.\n";

<?php
/**
 * Trust Verification — web install (plugin pages only, no wrteam core).
 *
 * Prerequisites:
 *   1. Fresh backup: bash scripts/final-sukoon-complete-backup.sh
 *   2. SKIP_BACKUP_CHECK=1 php install-trust-verification-web.php
 */
declare(strict_types=1);

if (getenv('SKIP_BACKUP_CHECK') !== '1') {
    $repoRoot = dirname(__DIR__);
    fwrite(STDERR, "Run backup first:\n  bash {$repoRoot}/scripts/final-sukoon-complete-backup.sh\n");
    fwrite(STDERR, "Then: SKIP_BACKUP_CHECK=1 php install-trust-verification-web.php\n");
    exit(2);
}

$homesRoot = '/www/wwwroot/homes.sukoon.group';
$repoRoot = dirname(__DIR__);
$fix = $repoRoot . '/web-fix';

if (!is_dir($homesRoot)) {
    fwrite(STDERR, "ERROR: {$homesRoot} not found\n");
    exit(1);
}

$pluginSrc = $fix . '/plugins/trust-verification';
$pluginDest = $homesRoot . '/src/plugins/trust-verification';

$copyTree = function (string $src, string $dest) use (&$copyTree, $pluginSrc): void {
    if (!is_dir($src)) {
        return;
    }
    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }
    foreach (scandir($src) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        $from = $src . '/' . $name;
        $to = $dest . '/' . $name;
        if (is_dir($from)) {
            $copyTree($from, $to);
            echo 'Plugin dir: ' . str_replace($pluginSrc . '/', '', $from) . "\n";
        } elseif (is_file($from)) {
            copy($from, $to);
            echo 'Plugin: ' . str_replace($pluginSrc . '/', '', $from) . "\n";
        }
    }
};
$copyTree($pluginSrc, $pluginDest);

$pages = [
    'pages-verification-index.jsx' => 'pages/verification/index.jsx',
    'pages-tenant-verification-in-barmer.jsx' => 'pages/tenant-verification-in-barmer/index.jsx',
    'pages-owner-verification-in-barmer.jsx' => 'pages/owner-verification-in-barmer/index.jsx',
    'pages-my-verification-orders.jsx' => 'pages/my-verification-orders/index.jsx',
    'pages-trust-verification-payment-return.jsx' => 'pages/payment/trust-verification-complete/index.jsx',
    'pages-trust-verification-ui-demo.jsx' => 'pages/trust-verification-ui-demo/index.jsx',
    'pages-verification-terms.jsx' => 'pages/verification-terms/index.jsx',
    'pages-verification-privacy.jsx' => 'pages/verification-privacy/index.jsx',
    'pages-verification-refund-policy.jsx' => 'pages/verification-refund-policy/index.jsx',
    'pages-tenant-verification-redirect.jsx' => 'pages/tenant-verification/index.jsx',
    'pages-owner-verification-redirect.jsx' => 'pages/owner-verification/index.jsx',
    'pages-tenant-verification-in-citySlug.jsx' => 'pages/tenant-verification-in/[citySlug]/index.jsx',
    'pages-owner-verification-in-citySlug.jsx' => 'pages/owner-verification-in/[citySlug]/index.jsx',
];

foreach ($pages as $srcName => $destRel) {
    $src = $fix . '/' . $srcName;
    $dest = $homesRoot . '/' . $destRel;
    if (!is_file($src)) {
        continue;
    }
    mkdir(dirname($dest), 0755, true);
    copy($src, $dest);
    echo "Page: {$destRel}\n";
}

$patchRewrites = $fix . '/patch-trust-verification-next-rewrites.js';
if (is_file($patchRewrites)) {
    echo "Patching next.config.js rewrites ...\n";
    passthru('node ' . escapeshellarg($patchRewrites), $patchCode);
}

$patchTailwind = $fix . '/patch-trust-verification-tailwind-content.js';
if (is_file($patchTailwind)) {
    echo "Patching tailwind.config.js content paths ...\n";
    passthru('node ' . escapeshellarg($patchTailwind), $twCode);
}

chdir($homesRoot);
echo "npm run build ...\n";
passthru('npm run build', $code);
exit($code === 0 ? 0 : 1);

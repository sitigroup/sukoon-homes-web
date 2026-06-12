<?php
/**
 * Patch core CashfreePayment to send TV trust-verification webhooks to the plugin endpoint.
 * Run from admin root: php /path/to/web-fix/patch-cashfree-tv-notify-url.php
 */
$target = __DIR__.'/../admin-homes/app/Services/Payment/CashfreePayment.php';
if (! is_file($target)) {
    $target = '/www/wwwroot/admin-homes/app/Services/Payment/CashfreePayment.php';
}
if (! is_file($target)) {
    fwrite(STDERR, "CashfreePayment.php not found\n");
    exit(1);
}
$src = file_get_contents($target);
$needle = "\$notify_url = url('/webhook/cashfree');";
$insert = <<<'PHP'
$notify_url = url('/webhook/cashfree');
            if (! empty($customMetaData['link_notes']['trust_verification_order_id'])) {
                $notify_url = url('/api/trust-verification/webhook/cashfree');
            }
PHP;
if (str_contains($src, 'trust-verification/webhook/cashfree')) {
    echo "Already patched: $target\n";
    exit(0);
}
if (! str_contains($src, $needle)) {
    fwrite(STDERR, "Anchor not found in $target\n");
    exit(1);
}
$src = str_replace($needle, $insert, $src);
file_put_contents($target, $src);
echo "Patched: $target\n";

<?php
/**
 * Re-apply email forgot-password fix on admin-homes (AuthApiController).
 * Run on server: php patch-email-forgot-password.php
 */
$path = __DIR__.'/app/Http/Controllers/Api/AuthApiController.php';
$src = __DIR__.'/web-fix/AuthApiController.php';
if (! is_file($src)) {
    $src = dirname(__DIR__).'/web-fix/AuthApiController.php';
}
if (! is_file($path)) {
    fwrite(STDERR, "AuthApiController not found at {$path}\n");
    exit(1);
}
if (! is_file($src)) {
    fwrite(STDERR, "Source patch file not found\n");
    exit(1);
}
copy($src, $path);
echo "Patched: {$path}\n";

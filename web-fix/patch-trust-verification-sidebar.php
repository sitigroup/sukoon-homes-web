<?php
/**
 * Add Trust Verification link to admin sidebar (Users submenu).
 * Run after backup: php patch-trust-verification-sidebar.php
 */
declare(strict_types=1);

$admin = is_file(getcwd() . '/artisan') ? getcwd() : '/www/wwwroot/admin-homes';
$sidebar = $admin . '/resources/views/layouts/sidebar.blade.php';

if (! is_file($sidebar)) {
    fwrite(STDERR, "Sidebar not found: {$sidebar}\n");
    exit(1);
}

$content = file_get_contents($sidebar);

if (str_contains($content, "route('trust-verification.index')")) {
    echo "sidebar.blade.php: Trust Verification menu already present\n";
    exit(0);
}

$needle = "                                    <a href=\"{{ route('verify-customer.form') }}\">\n                                        {{ __('Custom fields') }}\n                                    </a>\n                                </li>\n                            @endif";

$insert = "                                    <a href=\"{{ route('verify-customer.form') }}\">\n                                        {{ __('Custom fields') }}\n                                    </a>\n                                </li>\n                            @endif\n                            @if (has_permissions('read', 'customer'))\n                                <li class=\"submenu-item\">\n                                    <a href=\"{{ route('trust-verification.index') }}\">\n                                        {{ __('Trust Verification') }}\n                                    </a>\n                                </li>\n                            @endif";

if (! str_contains($content, $needle)) {
    fwrite(STDERR, "Sidebar anchor not found — add manually under Users submenu:\n");
    fwrite(STDERR, "  route('trust-verification.index') — Trust Verification\n");
    exit(1);
}

file_put_contents($sidebar, str_replace($needle, $insert, $content, $count));
if ($count !== 1) {
    fwrite(STDERR, "Sidebar patch failed (replace count {$count})\n");
    exit(1);
}

echo "sidebar.blade.php: Trust Verification menu added\n";

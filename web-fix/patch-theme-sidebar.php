<?php
/**
 * Add Appearance → Theme Settings link to admin sidebar (Web Settings submenu).
 * Run after backup: php patch-theme-sidebar.php
 */
declare(strict_types=1);

$admin = is_file(getcwd() . '/artisan') ? getcwd() : '/www/wwwroot/admin-homes';
$sidebar = $admin . '/resources/views/layouts/sidebar.blade.php';

if (! is_file($sidebar)) {
    fwrite(STDERR, "Sidebar not found: {$sidebar}\n");
    exit(1);
}

$content = file_get_contents($sidebar);

if (str_contains($content, "route('appearance.theme.index')")) {
    echo "sidebar.blade.php: Theme Settings menu already present\n";
    exit(0);
}

$needle = "                            @if (has_permissions('read', 'web_settings'))
                                <li class=\"submenu-item\">
                                    <a href=\"{{ url('web-settings') }}\">{{ __('Web Settings') }}</a>
                                </li>
                            @endif";

$insert = "                            @if (has_permissions('read', 'web_settings'))
                                <li class=\"submenu-item\">
                                    <a href=\"{{ url('web-settings') }}\">{{ __('Web Settings') }}</a>
                                </li>
                                <li class=\"submenu-item\">
                                    <a href=\"{{ route('appearance.theme.index') }}\">{{ __('Theme Settings') }}</a>
                                </li>
                            @endif";

if (str_contains($content, $needle)) {
    file_put_contents($sidebar, str_replace($needle, $insert, $content, $count));
    if ($count === 1) {
        echo "sidebar.blade.php: Theme Settings added under Web Settings\n";
        exit(0);
    }
    fwrite(STDERR, "Sidebar patch failed (replace count {$count})\n");
    exit(1);
}

fwrite(STDERR, "Sidebar anchor not found — add manually under Web Settings submenu:\n");
fwrite(STDERR, "  route('appearance.theme.index') — Theme Settings\n");
fwrite(STDERR, "  @if (has_permissions('read', 'web_settings'))\n");
exit(1);

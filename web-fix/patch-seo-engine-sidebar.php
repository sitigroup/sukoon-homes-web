<?php
/**
 * Add SEO Engine link to admin sidebar (Settings submenu).
 * Run after backup: php patch-seo-engine-sidebar.php
 */
declare(strict_types=1);

$admin = is_file(getcwd() . '/artisan') ? getcwd() : '/www/wwwroot/admin-homes';
$sidebar = $admin . '/resources/views/layouts/sidebar.blade.php';

if (! is_file($sidebar)) {
    fwrite(STDERR, "Sidebar not found: {$sidebar}\n");
    exit(1);
}

$backup = $sidebar . '.bak-seo-engine-' . date('Ymd-His');
if (! copy($sidebar, $backup)) {
    fwrite(STDERR, "Could not create backup: {$backup}\n");
    exit(1);
}
echo "Backup created: {$backup}\n";

$content = file_get_contents($sidebar);

if (str_contains($content, "route('seo-engine.dashboard')")) {
    echo "sidebar.blade.php: SEO Engine menu already present\n";
    exit(0);
}

$needle = "                            {{-- SEO settings --}}\n                            @if (has_permissions('read', 'seo_settings'))\n                                <li class=\"submenu-item\">\n                                    <a href=\"{{ url('seo_settings') }}\">{{ __('SEO Settings') }}</a>\n                                </li>\n                            @endif";

$insert = "                            {{-- SEO Engine plugin --}}\n                            @if (\n                                has_permissions('dashboard', 'seo_engine') ||\n                                has_permissions('settings', 'seo_engine')\n                            )\n                                <li class=\"submenu-item\">\n                                    <a href=\"{{ route('seo-engine.dashboard') }}\">{{ __('SEO Engine') }}</a>\n                                </li>\n                            @endif\n\n" . $needle;

if (! str_contains($content, $needle)) {
    fwrite(STDERR, "Sidebar anchor not found — add SEO Engine menu manually before SEO Settings.\n");
    exit(1);
}

file_put_contents($sidebar, str_replace($needle, $insert, $content, $count));
if ($count !== 1) {
    fwrite(STDERR, "Sidebar patch failed (replace count {$count})\n");
    exit(1);
}

echo "sidebar.blade.php: SEO Engine menu added\n";

<?php
/**
 * Add WhatsApp links to admin sidebar (Verification & Agreements group).
 * Run after backup: php patch-whatsapp-sidebar.php
 */
declare(strict_types=1);

$admin = is_file(getcwd() . '/artisan') ? getcwd() : '/www/wwwroot/admin-homes';
$sidebar = $admin . '/resources/views/layouts/sidebar.blade.php';

if (! is_file($sidebar)) {
    fwrite(STDERR, "Sidebar not found: {$sidebar}\n");
    exit(1);
}

$backup = $sidebar . '.bak-whatsapp-' . date('Ymd-His');
if (! copy($sidebar, $backup)) {
    fwrite(STDERR, "Could not create backup: {$backup}\n");
    exit(1);
}
echo "Backup created: {$backup}\n";

$content = file_get_contents($sidebar);

if (str_contains($content, "route('whatsapp.inbox.index')")) {
    echo "sidebar.blade.php: WhatsApp menu already present\n";
    exit(0);
}

$needle = "            <li class=\"submenu-item\">\n                <a href=\"{{ route('admin.rental-agreements.analytics') }}\">\n                    <i class=\"bi bi-bar-chart-line\"></i>\n                    <span>{{ __('Agreement Analytics') }}</span>\n                </a>\n            </li>\n        @endif";

$insert = "            <li class=\"submenu-item\">\n                <a href=\"{{ route('admin.rental-agreements.analytics') }}\">\n                    <i class=\"bi bi-bar-chart-line\"></i>\n                    <span>{{ __('Agreement Analytics') }}</span>\n                </a>\n            </li>\n        @endif\n        @if (\n            has_permissions('settings', 'whatsapp') ||\n            has_permissions('templates', 'whatsapp') ||\n            has_permissions('inbox', 'whatsapp')\n        )\n            <li class=\"submenu-item\">\n                <a href=\"{{ route('whatsapp.inbox.index') }}\">\n                    <i class=\"bi bi-whatsapp\"></i>\n                    <span>{{ __('WhatsApp Inbox') }}</span>\n                </a>\n            </li>\n            <li class=\"submenu-item\">\n                <a href=\"{{ route('whatsapp.templates.index') }}\">\n                    <i class=\"bi bi-chat-square-text\"></i>\n                    <span>{{ __('WhatsApp Templates') }}</span>\n                </a>\n            </li>\n            <li class=\"submenu-item\">\n                <a href=\"{{ route('whatsapp.settings.index') }}\">\n                    <i class=\"bi bi-gear\"></i>\n                    <span>{{ __('WhatsApp Settings') }}</span>\n                </a>\n            </li>\n        @endif";

if (! str_contains($content, $needle)) {
    fwrite(STDERR, "Sidebar anchor not found — add manually near Verification & Agreements group.\n");
    exit(1);
}

file_put_contents($sidebar, str_replace($needle, $insert, $content, $count));
if ($count !== 1) {
    fwrite(STDERR, "Sidebar patch failed (replace count {$count})\n");
    exit(1);
}

echo "sidebar.blade.php: WhatsApp menu added\n";


<?php
/**
 * Remove legacy WhatsApp submenu items from Verification & Agreements group
 * after separate WhatsApp group is introduced.
 */
declare(strict_types=1);

$admin = is_file(getcwd() . '/artisan') ? getcwd() : '/www/wwwroot/admin-homes';
$sidebar = $admin . '/resources/views/layouts/sidebar.blade.php';

if (! is_file($sidebar)) {
    fwrite(STDERR, "Sidebar not found: {$sidebar}\n");
    exit(1);
}

$backup = $sidebar . '.bak-whatsapp-clean-' . date('Ymd-His');
copy($sidebar, $backup);
echo "Backup created: {$backup}\n";

$content = file_get_contents($sidebar);

$pattern = '/\n\s*@if\s*\(\s*has_permissions\(\'settings\', \'whatsapp\'\)\s*\|\|\s*has_permissions\(\'templates\', \'whatsapp\'\)\s*\|\|\s*has_permissions\(\'inbox\', \'whatsapp\'\)\s*\)\s*\n\s*<li class="submenu-item">\s*\n\s*<a href="\{\{ route\(\'whatsapp\.inbox\.index\'\) \}\}">[\s\S]*?\n\s*@endif\s*\n/s';
$new = preg_replace($pattern, "\n", $content, 1, $count);

if ($count === 0) {
    echo "No legacy WhatsApp block found to remove.\n";
    exit(0);
}

file_put_contents($sidebar, $new);
echo "Removed legacy WhatsApp submenu block.\n";


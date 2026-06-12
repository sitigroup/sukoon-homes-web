<?php
/**
 * Move WhatsApp links into a separate sidebar group.
 * Run: php patch-whatsapp-sidebar-separate.php
 */
declare(strict_types=1);

$admin = is_file(getcwd() . '/artisan') ? getcwd() : '/www/wwwroot/admin-homes';
$sidebar = $admin . '/resources/views/layouts/sidebar.blade.php';

if (! is_file($sidebar)) {
    fwrite(STDERR, "Sidebar not found: {$sidebar}\n");
    exit(1);
}

$backup = $sidebar . '.bak-whatsapp-separate-' . date('Ymd-His');
if (! copy($sidebar, $backup)) {
    fwrite(STDERR, "Could not create backup: {$backup}\n");
    exit(1);
}
echo "Backup created: {$backup}\n";

$content = file_get_contents($sidebar);

$oldWhatsappInVerification = <<<'BLADE'
        @if (
            has_permissions('settings', 'whatsapp') ||
            has_permissions('templates', 'whatsapp') ||
            has_permissions('inbox', 'whatsapp')
        )
            <li class="submenu-item">
                <a href="{{ route('whatsapp.inbox.index') }}">
                    <i class="bi bi-whatsapp"></i>
                    <span>{{ __('WhatsApp Inbox') }}</span>
                </a>
            </li>
            <li class="submenu-item">
                <a href="{{ route('whatsapp.templates.index') }}">
                    <i class="bi bi-chat-square-text"></i>
                    <span>{{ __('WhatsApp Templates') }}</span>
                </a>
            </li>
            <li class="submenu-item">
                <a href="{{ route('whatsapp.settings.index') }}">
                    <i class="bi bi-gear"></i>
                    <span>{{ __('WhatsApp Settings') }}</span>
                </a>
            </li>
        @endif
BLADE;

if (str_contains($content, $oldWhatsappInVerification)) {
    $content = str_replace($oldWhatsappInVerification . "\n", '', $content);
}

if (str_contains($content, "route('whatsapp.inbox.index')") && str_contains($content, "{{ __('WhatsApp') }}")) {
    file_put_contents($sidebar, $content);
    echo "WhatsApp separate group already present.\n";
    exit(0);
}

$anchor = "{{-- Group 2: Tenancy Lifecycle --}}";
$newGroup = <<<'BLADE'
{{-- Group: WhatsApp --}}
@if (
    has_permissions('settings', 'whatsapp') ||
    has_permissions('templates', 'whatsapp') ||
    has_permissions('inbox', 'whatsapp')
)
<li class="sidebar-item has-sub">
    <a href="#" class="sidebar-link">
        <i class="bi bi-whatsapp"></i>
        <span class="menu-item">{{ __('WhatsApp') }}</span>
    </a>
    <ul class="submenu" style="padding-left: 0rem">
        <li class="submenu-item">
            <a href="{{ route('whatsapp.inbox.index') }}">
                <i class="bi bi-chat-left-text"></i>
                <span>{{ __('Inbox') }}</span>
            </a>
        </li>
        <li class="submenu-item">
            <a href="{{ route('whatsapp.templates.index') }}">
                <i class="bi bi-chat-square-text"></i>
                <span>{{ __('Templates') }}</span>
            </a>
        </li>
        <li class="submenu-item">
            <a href="{{ route('whatsapp.settings.index') }}">
                <i class="bi bi-gear"></i>
                <span>{{ __('Settings') }}</span>
            </a>
        </li>
    </ul>
</li>
@endif

BLADE;

if (! str_contains($content, $anchor)) {
    fwrite(STDERR, "Sidebar anchor not found for separate group.\n");
    exit(1);
}

$content = str_replace($anchor, $newGroup . $anchor, $content, $count);
if ($count !== 1) {
    fwrite(STDERR, "Sidebar patch failed (replace count {$count})\n");
    exit(1);
}

file_put_contents($sidebar, $content);
echo "sidebar.blade.php: WhatsApp moved to separate group\n";


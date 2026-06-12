<?php
/**
 * Inject Sukoon Theme CSS variables into admin layout <head>.
 * Run after backup: php patch-theme-admin-head.php
 */
declare(strict_types=1);

$admin = is_file(getcwd() . '/artisan') ? getcwd() : '/www/wwwroot/admin-homes';
$layout = $admin . '/resources/views/layouts/main.blade.php';

if (! is_file($layout)) {
    fwrite(STDERR, "Layout not found: {$layout}\n");
    exit(1);
}

$content = file_get_contents($layout);
$inject = "@include('theme::admin.partials.admin-theme-inject')";

if (str_contains($content, $inject) || str_contains($content, 'admin-theme-inject')) {
    echo "main.blade.php: theme inject already present\n";
    exit(0);
}

$anchors = ['</head>', '@yield(\'css\')', "@yield('css')"];

foreach ($anchors as $needle) {
    if (str_contains($content, $needle)) {
        $replacement = "    {$inject}\n    {$needle}";
        if ($needle === '</head>') {
            $replacement = "    {$inject}\n</head>";
        }
        $content = str_replace($needle, $replacement, $content, $count);
        if ($count >= 1) {
            file_put_contents($layout, $content);
            echo "main.blade.php: admin theme inject added\n";
            exit(0);
        }
    }
}

fwrite(STDERR, "Could not find </head> anchor — add manually before </head>:\n  {$inject}\n");
exit(1);

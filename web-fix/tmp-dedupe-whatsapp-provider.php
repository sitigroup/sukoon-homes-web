<?php
$f = '/www/wwwroot/admin-homes/config/app.php';
$c = file_get_contents($f);
$needle = 'App\\Plugins\\Whatsapp\\WhatsappServiceProvider::class,';
if (substr_count($c, $needle) > 1) {
    $first = strpos($c, $needle);
    $prefix = substr($c, 0, $first + strlen($needle));
    $suffix = substr($c, $first + strlen($needle));
    $suffix = str_replace($needle, '', $suffix);
    file_put_contents($f, $prefix . $suffix);
    echo "DEDUPED\n";
} else {
    echo "OK\n";
}
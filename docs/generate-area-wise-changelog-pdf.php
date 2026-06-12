<?php

/**
 * Generate Area Wise Plugin changelog PDF using admin-homes dompdf.
 * Run on server: php /tmp/generate-area-wise-changelog-pdf.php
 */

$autoload = '/www/wwwroot/admin-homes/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "dompdf autoload not found at {$autoload}\n");
    exit(1);
}

require_once $autoload;

use Dompdf\Dompdf;
use Dompdf\Options;

$htmlPath = '/tmp/area-wise-changelog-v2.html';
$pdfPath = '/tmp/Area-Wise-Plugin-CHANGELOG-v2.0.0.pdf';

if (!is_file($htmlPath)) {
    fwrite(STDERR, "HTML not found: {$htmlPath}\n");
    exit(1);
}

$html = file_get_contents($htmlPath);

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
file_put_contents($pdfPath, $dompdf->output());

echo "OK: {$pdfPath}\n";

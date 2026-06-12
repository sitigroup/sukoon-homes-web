<?php
$base = '/www/wwwroot/homes.sukoon.group';
$utilSrc = __DIR__ . '/openGoogleMapsDirections.js';
$utilDst = $base . '/src/utils/openGoogleMapsDirections.js';
copy($utilSrc, $utilDst);
echo "copied util\n";

$files = [
    'src/components/property-detail/UserPropertyDetails.jsx',
    'src/components/property-detail/PropertyDetails.jsx',
    'src/components/property-detail/AgentPropertyDetails.jsx',
    'src/components/project-details/UserProjectDetails.jsx',
    'src/components/project-details/ProjectDetails.jsx',
    'src/components/project-details/AgentProjectDetails.jsx',
    'src/components/property-detail/PropertyAddress.jsx',
];

$import = "import { openDirectionsForListing } from \"@/utils/openGoogleMapsDirections\";";

$openDirectionsFn = <<<'RE'
  const openDirections = (latitude, longitude) => {
    const lat = Number(latitude);
    const lng = Number(longitude);
    if (!Number.isFinite(lat) || !Number.isFinite(lng) || (lat === 0 && lng === 0)) {
      return;
    }
    window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`, "_blank", "noopener,noreferrer");
  };
RE;

foreach ($files as $rel) {
    $path = $base . '/' . $rel;
    if (!is_file($path)) {
        echo "skip missing $rel\n";
        continue;
    }
    $c = file_get_contents($path);

    if ($rel === 'src/components/property-detail/PropertyAddress.jsx') {
        $c = str_replace(
            '{t("getDirections") || "Get Directions"}',
            '{t("getDirections") !== "getDirections" ? t("getDirections") : "Get Directions"}',
            $c
        );
        file_put_contents($path, $c);
        echo "patched PropertyAddress label\n";
        continue;
    }

    if (!str_contains($c, 'openDirectionsForListing')) {
        if (str_contains($c, "from \"@/redux/slices/authSlice\"")) {
            $c = str_replace(
                "from \"@/redux/slices/authSlice\";",
                "from \"@/redux/slices/authSlice\";\n" . $import,
                $c
            );
        } elseif (str_contains($c, "from '@/redux/slices/authSlice'")) {
            $c = str_replace(
                "from '@/redux/slices/authSlice';",
                "from '@/redux/slices/authSlice';\n" . $import,
                $c
            );
        } else {
            $c = preg_replace('/^(import .+;\n)/m', '$1' . $import . "\n", $c, 1);
        }
    }

    $c = str_replace($openDirectionsFn, '', $c);
    $c = preg_replace(
        '/openDirections\(\s*[^)]*?(?:propertyDetails|projectDetails)[^)]*\)/',
        'openDirectionsForListing($1Details)',
        $c
    );

    // Fix replacement - manual patterns
    $patterns = [
        'openDirections(propertyDetails?.latitude, propertyDetails?.longitude)' => 'openDirectionsForListing(propertyDetails)',
        'openDirections(projectDetails?.latitude, projectDetails?.longitude)' => 'openDirectionsForListing(projectDetails)',
    ];
    foreach ($patterns as $from => $to) {
        $c = str_replace($from, $to, $c);
    }

    file_put_contents($path, $c);
    echo "patched $rel\n";
}

// en.json translation
$en = $base . '/src/utils/en.json';
$data = json_decode(file_get_contents($en), true);
$data['getDirections'] = 'Get Directions';
file_put_contents($en, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
echo "en.json getDirections\n";

passthru("cd $base && npm run build 2>&1 | tail -8");
passthru("pm2 restart homes-sukoon 2>&1 | tail -3");

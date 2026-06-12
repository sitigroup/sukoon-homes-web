<?php
$base = '/www/wwwroot/homes.sukoon.group';
$import = 'import { normalizeLatLng, openDirectionsForListing } from "@/utils/openGoogleMapsDirections";';
$openFn = <<<'FN'
  const openDirections = (latitude, longitude) => {
    const lat = Number(latitude);
    const lng = Number(longitude);
    if (!Number.isFinite(lat) || !Number.isFinite(lng) || (lat === 0 && lng === 0)) {
      return;
    }
    window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`, "_blank", "noopener,noreferrer");
  };
FN;

$targets = [
    "$base/src/components/property-detail/AgentPropertyDetails.jsx",
    "$base/src/components/project-details/ProjectDetails.jsx",
    "$base/src/components/project-details/AgentProjectDetails.jsx",
];

foreach ($targets as $path) {
    if (!is_file($path)) {
        echo "missing $path\n";
        continue;
    }
    $c = file_get_contents($path);
    $detailsVar = str_contains($path, 'Project') ? 'projectDetails' : 'propertyDetails';

    if (!str_contains($c, 'openDirectionsForListing')) {
        if (str_contains($c, 'from "@/utils/helperFunction"')) {
            $c = str_replace(
                'from "@/utils/helperFunction";',
                'from "@/utils/helperFunction";' . "\n" . $import,
                $c
            );
        } elseif (str_contains($c, "from '@/utils/helperFunction'")) {
            $c = str_replace(
                "from '@/utils/helperFunction';",
                "from '@/utils/helperFunction';\n" . $import,
                $c
            );
        } else {
            $c = preg_replace('/^(import .+;\r?\n)/', '$1' . $import . "\n", $c, 1);
        }
    }

    $c = str_replace($openFn, '', $c);
    $c = str_replace(
        "openDirections({$detailsVar}?.latitude, {$detailsVar}?.longitude)",
        "openDirectionsForListing({$detailsVar})",
        $c
    );

    if (!str_contains($c, 'const mapCoords = normalizeLatLng')) {
        $mapBlock = <<<MAP

  const mapCoords = normalizeLatLng(
    {$detailsVar}?.area_listing?.latitude ?? {$detailsVar}?.latitude,
    {$detailsVar}?.area_listing?.longitude ?? {$detailsVar}?.longitude,
  );
MAP;
        $c = preg_replace(
            '/(const handleOpenGoogleMap = \(\) => \{)/',
            $mapBlock . "\n\n  $1",
            $c,
            1
        );
        $c = str_replace(
            "latitude={{$detailsVar}?.latitude}\n                    longitude={{$detailsVar}?.longitude}",
            "latitude={mapCoords?.lat ?? {$detailsVar}?.latitude}\n                    longitude={mapCoords?.lng ?? {$detailsVar}?.longitude}",
            $c
        );
        $c = preg_replace(
            '/\s+' . preg_quote($detailsVar, '/') . '\?->latitude &&\s+\n\s+' . preg_quote($detailsVar, '/') . '\?->longitude &&/',
            " (mapCoords || {$detailsVar}?.latitude) &&\n                (mapCoords || {$detailsVar}?.longitude) &&",
            $c,
            1
        );
    }

    file_put_contents($path, $c);
    echo "ok $path\n";
}

<?php

$path = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Services/AreaListingService.php';
$content = file_get_contents($path);

if (strpos($content, '$canAutoCreate = self::canAutoCreateAreasOnSave();') !== false) {
    echo "already patched\n";
    exit(0);
}

$needle = <<<'PHP'
        if (empty($areaId) && $detectedArea !== '') {
            $detectedArea = self::clean($detectedArea);
            $detectedAreaNormalized = self::normalized($detectedArea);
            $cityIdForArea = $city?->id ?: (self::pickScalar($request->input('city_id')) ? (int) $request->input('city_id') : null);

            if ($cityIdForArea) {
                $similarArea = self::findSimilarAreaForCity($cityIdForArea, $detectedAreaNormalized);
                if ($similarArea) {
                    Log::info('AreaListing: Used similar existing area: '.$similarArea->name.' instead of detected: '.$detectedArea);
                    $areaId = $similarArea->id;
                    $selectedArea = $similarArea;
                }
            }

            if (empty($areaId)) {
                if (self::canAutoCreateAreasOnSave()) {
                    if ($cityIdForArea) {
                        $area = Area::firstOrCreate(
PHP;

$replace = <<<'PHP'
        $canAutoCreate = self::canAutoCreateAreasOnSave();

        if (empty($areaId) && $detectedArea !== '') {
            $detectedArea = self::clean($detectedArea);
            $detectedAreaNormalized = self::normalized($detectedArea);
            $cityIdForArea = $city?->id ?: (self::pickScalar($request->input('city_id')) ? (int) $request->input('city_id') : null);

            if ($canAutoCreate && $cityIdForArea) {
                $similarArea = self::findSimilarAreaForCity($cityIdForArea, $detectedAreaNormalized);
                if ($similarArea) {
                    Log::info('AreaListing: Used similar existing area: '.$similarArea->name.' instead of detected: '.$detectedArea);
                    $areaId = $similarArea->id;
                    $selectedArea = $similarArea;
                }
            }

            if (empty($areaId)) {
                if ($canAutoCreate && $cityIdForArea) {
                        $area = Area::firstOrCreate(
PHP;

if (strpos($content, $needle) === false) {
    echo "area block needle not found\n";
    exit(1);
}
$content = str_replace($needle, $replace, $content);

$needle2 = <<<'PHP'
        if (empty($subAreaId) && $detectedSubArea !== '' && ! empty($areaId)) {
            $detectedSubArea = self::clean($detectedSubArea);
            $detectedSubNormalized = self::normalized($detectedSubArea);
            $similarSubArea = self::findSimilarSubAreaForArea((int) $areaId, $detectedSubNormalized);
            if ($similarSubArea) {
                Log::info('AreaListing: Used similar existing sub-area: '.$similarSubArea->name.' instead of detected: '.$detectedSubArea);
                $subAreaId = $similarSubArea->id;
                $selectedSubArea = $similarSubArea;
            } elseif (self::canAutoCreateAreasOnSave()) {
PHP;

$replace2 = <<<'PHP
        if (empty($subAreaId) && $detectedSubArea !== '' && ! empty($areaId)) {
            $detectedSubArea = self::clean($detectedSubArea);
            $detectedSubNormalized = self::normalized($detectedSubArea);
            if ($canAutoCreate) {
                $similarSubArea = self::findSimilarSubAreaForArea((int) $areaId, $detectedSubNormalized);
                if ($similarSubArea) {
                    Log::info('AreaListing: Used similar existing sub-area: '.$similarSubArea->name.' instead of detected: '.$detectedSubArea);
                    $subAreaId = $similarSubArea->id;
                    $selectedSubArea = $similarSubArea;
                } else {
PHP;

if (strpos($content, $needle2) === false) {
    echo "sub-area block needle not found\n";
    exit(1);
}
$content = str_replace($needle2, $replace2, $content);

$needle3 = <<<'PHP
                $subAreaId = $subArea->id;
                $selectedSubArea = $subArea;
            } else {
                self::storePendingSuggestion('sub_area', [
PHP;

$replace3 = <<<'PHP
                $subAreaId = $subArea->id;
                $selectedSubArea = $subArea;
                }
            } else {
                self::storePendingSuggestion('sub_area', [
PHP;

if (strpos($content, $needle3) === false) {
    echo "sub-area close brace needle not found\n";
    exit(1);
}
$content = str_replace($needle3, $replace3, $content);

file_put_contents($path, $content);
echo "saveListingLocation: non-privileged users no longer auto-link or auto-create areas\n";

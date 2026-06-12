<?php

$path = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Services/AreaListingService.php';
$content = file_get_contents($path);

if (strpos($content, '$canAutoCreate = self::canAutoCreateAreasOnSave();') !== false) {
    echo "already patched\n";
    exit(0);
}

$content = str_replace(
    "        ) : null;\n\n        if (empty(\$areaId) && \$detectedArea !== '') {",
    "        ) : null;\n\n        \$canAutoCreate = self::canAutoCreateAreasOnSave();\n\n        if (empty(\$areaId) && \$detectedArea !== '') {",
    $content
);

$content = str_replace(
    "            if (\$cityIdForArea) {\n                \$similarArea = self::findSimilarAreaForCity(\$cityIdForArea, \$detectedAreaNormalized);",
    "            if (\$canAutoCreate && \$cityIdForArea) {\n                \$similarArea = self::findSimilarAreaForCity(\$cityIdForArea, \$detectedAreaNormalized);",
    $content
);

$content = str_replace(
    "            if (empty(\$areaId)) {\n                if (self::canAutoCreateAreasOnSave()) {\n                    if (\$cityIdForArea) {",
    "            if (empty(\$areaId)) {\n                if (\$canAutoCreate && \$cityIdForArea) {",
    $content
);

$content = str_replace(
    "            \$detectedSubNormalized = self::normalized(\$detectedSubArea);\n            \$similarSubArea = self::findSimilarSubAreaForArea((int) \$areaId, \$detectedSubNormalized);\n            if (\$similarSubArea) {\n                Log::info('AreaListing: Used similar existing sub-area: '.\$similarSubArea->name.' instead of detected: '.\$detectedSubArea);\n                \$subAreaId = \$similarSubArea->id;\n                \$selectedSubArea = \$similarSubArea;\n            } elseif (self::canAutoCreateAreasOnSave()) {\n                \$subArea = SubArea::firstOrCreate(",
    "            \$detectedSubNormalized = self::normalized(\$detectedSubArea);\n            if (\$canAutoCreate) {\n                \$similarSubArea = self::findSimilarSubAreaForArea((int) \$areaId, \$detectedSubNormalized);\n                if (\$similarSubArea) {\n                    Log::info('AreaListing: Used similar existing sub-area: '.\$similarSubArea->name.' instead of detected: '.\$detectedSubArea);\n                    \$subAreaId = \$similarSubArea->id;\n                    \$selectedSubArea = \$similarSubArea;\n                } else {\n                \$subArea = SubArea::firstOrCreate(",
    $content
);

$content = str_replace(
    "                \$subAreaId = \$subArea->id;\n                \$selectedSubArea = \$subArea;\n            } else {\n                self::storePendingSuggestion('sub_area', [",
    "                \$subAreaId = \$subArea->id;\n                \$selectedSubArea = \$subArea;\n                }\n            } else {\n                self::storePendingSuggestion('sub_area', [",
    $content
);

file_put_contents($path, $content);
echo "patched\n";

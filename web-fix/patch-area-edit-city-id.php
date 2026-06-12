<?php

$controllerPath = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Http/Controllers/Admin/AreaListingAdminController.php';
$indexPath = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/views/admin/index.blade.php';
$actionsPath = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/views/admin/partials/area-actions.blade.php';

$controller = file_get_contents($controllerPath);

if (! str_contains($controller, 'resolveCityIdForAreaRequest')) {
    $method = <<<'PHP'

    private function resolveCityIdForAreaRequest(Request $request): ?int
    {
        if ($request->filled('city_id')) {
            return (int) $request->input('city_id');
        }

        $city = $this->cleanName((string) $request->input('city', ''));
        $state = $this->cleanName((string) $request->input('state', ''));
        if ($city === '') {
            return null;
        }

        $query = DB::table('area_listing_cities')->where('name', $city);
        if ($state !== '') {
            $query->where('state', $state);
        }

        $row = $query->orderBy('id')->first();

        return $row ? (int) $row->id : null;
    }

PHP;

    $controller = str_replace(
        "    private function areaPayload(Request \$request): array\n    {",
        $method . "    private function areaPayload(Request \$request): array\n    {",
        $controller
    );

    $controller = str_replace(
        "'city_id' => \$request->input('city_id'),",
        "'city_id' => \$this->resolveCityIdForAreaRequest(\$request),",
        $controller
    );

    file_put_contents($controllerPath, $controller);
    echo "controller patched\n";
} else {
    echo "controller already patched\n";
}

$actions = file_get_contents($actionsPath);
if (! str_contains($actions, 'data-city-id')) {
    $actions = str_replace(
        "    data-name=\"{{ e(\$area->name) }}\"\n",
        "    data-name=\"{{ e(\$area->name) }}\"\n    data-city-id=\"{{ (int) (\$area->city_id ?? 0) }}\"\n",
        $actions
    );
    file_put_contents($actionsPath, $actions);
    echo "area-actions patched\n";
} else {
    echo "area-actions already patched\n";
}

$index = file_get_contents($indexPath);
$hidden = "                            <input type=\"hidden\" name=\"city_id\" id=\"editAreaCityIdInput\">\n";

if (! str_contains($index, 'editAreaCityIdInput')) {
    $index = str_replace(
        "                            <input type=\"hidden\" name=\"city\" id=\"editAreaCityInput\" required>\n",
        $hidden . "                            <input type=\"hidden\" name=\"city\" id=\"editAreaCityInput\" required>\n",
        $index
    );
    echo "index hidden city_id added\n";
}

if (! str_contains($index, "editAreaCityIdInput').val")) {
    $index = str_replace(
        "        $('#editAreaCityInput').val(city);\n",
        "        $('#editAreaCityInput').val(city);\n        $('#editAreaCityIdInput').val(btn.data('city-id') || '');\n",
        $index
    );
    echo "index js city_id added\n";
}

if (! str_contains($index, 'editAreaCityIdInput') || str_contains($index, "editAreaCityIdInput').val")) {
    file_put_contents($indexPath, $index);
}

// Fix Maharana row now
$boot = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$boot->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$updated = DB::table('area_listing_areas')
    ->where('id', 43)
    ->whereNull('city_id')
    ->update(['city_id' => 10, 'updated_at' => now()]);
echo "area 43 city_id fix rows={$updated}\n";

passthru('cd /www/wwwroot/admin-homes && sudo -u www php artisan view:clear 2>&1');

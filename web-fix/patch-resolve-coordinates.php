<?php

$controllerPath = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Http/Controllers/Admin/AreaListingAdminController.php';
$webPath = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/routes/web.php';

$controller = file_get_contents($controllerPath);

if (strpos($controller, 'AreaListingService') === false) {
    $controller = str_replace(
        "use App\Plugins\AreaListing\Services\AreaListingPropertyLocationRepairService;\n",
        "use App\Plugins\AreaListing\Services\AreaListingPropertyLocationRepairService;\nuse App\Plugins\AreaListing\Services\AreaListingService;\n",
        $controller
    );
}

$method = <<<'PHP'

    public function resolveCoordinates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'city_id' => 'nullable|integer',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'names' => 'nullable|array',
            'names.*' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $locationComponents = json_decode((string) $request->input('location_components', '[]'), true);
        if (! is_array($locationComponents)) {
            $locationComponents = [];
        }

        $data = AreaListingService::resolveNearestFromCoordinates(
            (float) $request->latitude,
            (float) $request->longitude,
            $request->filled('city_id') ? (int) $request->city_id : null,
            (string) $request->input('city', ''),
            (string) $request->input('state', ''),
            (string) $request->input('country', 'India'),
            $locationComponents
        );

        return response()->json([
            'error' => false,
            'message' => $data ? __('Location resolved') : __('No matching area found'),
            'data' => $data,
        ]);
    }
PHP;

if (strpos($controller, 'function resolveCoordinates') === false) {
    $controller = preg_replace('/\n}\s*$/', $method . "\n}\n", $controller, 1);
    file_put_contents($controllerPath, $controller);
    echo "controller patched\n";
} else {
    echo "controller already patched\n";
}

$web = file_get_contents($webPath);
$route = "    Route::post('resolve-coordinates', [AreaListingAdminController::class, 'resolveCoordinates'])->name('resolve-coordinates');\n";

if (strpos($web, 'resolve-coordinates') === false) {
    $web = str_replace(
        "    Route::post('drift-check/sync-execute', [AreaListingAdminController::class, 'syncSnapshotsExecute'])->name('drift-check.sync-execute');\n",
        "    Route::post('drift-check/sync-execute', [AreaListingAdminController::class, 'syncSnapshotsExecute'])->name('drift-check.sync-execute');\n" . $route,
        $web
    );
    file_put_contents($webPath, $web);
    echo "route patched\n";
} else {
    echo "route already patched\n";
}

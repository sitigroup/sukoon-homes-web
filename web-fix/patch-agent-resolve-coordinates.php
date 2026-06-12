<?php

$controllerPath = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Http/Controllers/Api/AreaListingApiController.php';
$apiRoutesPath = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/routes/api.php';

$controller = file_get_contents($controllerPath);

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
            'location_components' => 'nullable|string',
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
    $controller = preg_replace(
        '/\n    private function normalizedName\(\$value\): string/',
        $method . "\n\n    private function normalizedName(\$value): string",
        $controller,
        1
    );
    file_put_contents($controllerPath, $controller);
    echo "controller: resolveCoordinates added\n";
} else {
    echo "controller: already has resolveCoordinates\n";
}

$routes = file_get_contents($apiRoutesPath);
$routeLine = "    Route::post('resolve-coordinates', [AreaListingApiController::class, 'resolveCoordinates']);\n";

if (strpos($routes, 'resolve-coordinates') === false) {
    $routes = str_replace(
        "    Route::post('sub-areas', [AreaListingApiController::class, 'storeSubArea']);\n});\n",
        "    Route::post('sub-areas', [AreaListingApiController::class, 'storeSubArea']);\n" . $routeLine . "});\n",
        $routes
    );
    file_put_contents($apiRoutesPath, $routes);
    echo "routes: resolve-coordinates added\n";
} else {
    echo "routes: already patched\n";
}

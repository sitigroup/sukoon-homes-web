<?php

$controllerPath = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Http/Controllers/Admin/AreaListingAdminController.php';
$controller = file_get_contents($controllerPath);

$newMethod = <<<'PHP'
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

if (preg_match('/\n    public function resolveCoordinates\(Request \$request\)\s*\{.*?\n    \}/s', $controller, $matches)) {
    $controller = str_replace($matches[0], "\n" . $newMethod, $controller);
    file_put_contents($controllerPath, $controller);
    echo "controller method updated\n";
} else {
    echo "resolveCoordinates method not found\n";
    exit(1);
}

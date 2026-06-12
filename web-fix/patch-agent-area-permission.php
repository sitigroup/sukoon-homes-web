<?php

$root = '/www/wwwroot/admin-homes';

function patchFile(string $path, array $replacements): void
{
    $content = file_get_contents($path);
    $original = $content;
    foreach ($replacements as $search => $replace) {
        if (! str_contains($content, $search)) {
            echo "SKIP (not found): {$path} :: " . substr($search, 0, 60) . "...\n";
            continue;
        }
        $content = str_replace($search, $replace, $content);
    }
    if ($content === $original) {
        echo "UNCHANGED: {$path}\n";
        return;
    }
    file_put_contents($path, $content);
    echo "PATCHED: {$path}\n";
}

// API route
$apiRoutes = $root . '/app/Plugins/AreaListing/routes/api.php';
patchFile($apiRoutes, [
    "Route::middleware('auth:sanctum')->prefix('area-listing')->group(function () {\n    Route::post('states'" =>
        "Route::middleware('auth:sanctum')->prefix('area-listing')->group(function () {\n    Route::get('permissions', [AreaListingApiController::class, 'permissions']);\n    Route::post('states'",
]);

// Customer model fillable + cast
$customerModel = $root . '/app/Models/Customer.php';
patchFile($customerModel, [
    "'is_agent_verified',\n        'created_at'" =>
        "'is_agent_verified',\n        'can_manage_area_listing',\n        'created_at'",
    "'is_agent_verified' => 'boolean'," =>
        "'is_agent_verified' => 'boolean',\n        'can_manage_area_listing' => 'boolean',",
]);

// CustomersController update
$customersController = $root . '/app/Http/Controllers/CustomersController.php';
patchFile($customersController, [
    "if (\$request->hasFile('profile')) {\n                \$path = config('global.USER_IMG_PATH');" =>
        "if (\$customer->is_agent && \$request->has('can_manage_area_listing')) {\n                \$updateData['can_manage_area_listing'] = \$request->boolean('can_manage_area_listing') ? 1 : 0;\n            }\n\n            if (\$request->hasFile('profile')) {\n                \$path = config('global.USER_IMG_PATH');",
    "'profile' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',\n            ]);" =>
        "'profile' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',\n                'can_manage_area_listing' => 'nullable|boolean',\n            ]);",
]);

// Customer edit blade — agent permission toggle
$customerEdit = $root . '/resources/views/customer/edit.blade.php';
patchFile($customerEdit, [
    "<div class=\"col-md-4 mb-3\">\n\t\t\t\t\t\t<label class=\"form-label fw-bold\">{{ __('Active Mode') }}</label>" =>
        "<div class=\"col-md-4 mb-3\">\n\t\t\t\t\t\t<label class=\"form-label fw-bold\">{{ __('Area Wise — direct create') }}</label>\n\t\t\t\t\t\t<div class=\"form-check form-switch mt-2\">\n\t\t\t\t\t\t\t<input class=\"form-check-input\" type=\"checkbox\" name=\"can_manage_area_listing\" id=\"can_manage_area_listing\" value=\"1\" @checked((bool) (\$customer->can_manage_area_listing ?? false))>\n\t\t\t\t\t\t\t<label class=\"form-check-label\" for=\"can_manage_area_listing\">{{ __('Allow this agent to add Area/Sub Area without admin approval (same as admin panel)') }}</label>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<small class=\"text-muted\">{{ __('Other agents still submit suggestions for approval.') }}</small>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"col-md-4 mb-3\">\n\t\t\t\t\t\t<label class=\"form-label fw-bold\">{{ __('Active Mode') }}</label>",
]);

// Profile API — expose flag to app
$profileApi = $root . '/app/Http/Controllers/Api/ProfileApiController.php';
if (is_file($profileApi)) {
    patchFile($profileApi, [
        "\$data['agent_profile'] = \$resolvedAgentProfile;" =>
            "\$data['agent_profile'] = \$resolvedAgentProfile;\n            if (class_exists(\\App\\Plugins\\AreaListing\\Services\\AreaListingService::class)) {\n                \$data['can_manage_area_listing'] = \\App\\Plugins\\AreaListing\\Services\\AreaListingService::userCanAutoCreateAreas(\$userData);\n            } else {\n                \$data['can_manage_area_listing'] = (bool) (\$userData->can_manage_area_listing ?? false);\n            }",
    ]);
}

echo "Done.\n";

<?php

$root = '/www/wwwroot/admin-homes';

function patchFile(string $path, array $replacements): void
{
    if (!is_file($path)) {
        echo "MISSING: {$path}\n";
        return;
    }
    $content = file_get_contents($path);
    $original = $content;
    foreach ($replacements as $search => $replace) {
        if (!str_contains($content, $search)) {
            echo "SKIP anchor in {$path}: " . substr($search, 0, 50) . "...\n";
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

$service = $root . '/app/Plugins/AreaListing/Services/AreaListingService.php';
patchFile($service, [
    "use Illuminate\\Support\\Facades\\DB;\nuse Illuminate\\Support\\Str;" =>
        "use App\\Models\\Customer;\nuse Illuminate\\Support\\Facades\\Auth;\nuse Illuminate\\Support\\Facades\\DB;\nuse Illuminate\\Support\\Str;",

    "    public static function canAutoCreateAreasOnSave(): bool\n    {\n        if (request()->boolean('area_listing_admin_save')) {\n            return true;\n        }\n\n        return self::userCanAutoCreateAreas(auth()->user());\n    }" =>
        "    public static function resolveAreaListingActor(): ?object\n    {\n        \$user = Auth::guard('sanctum')->user();\n        if (\$user) {\n            return \$user;\n        }\n\n        \$user = Auth::user();\n        if (\$user) {\n            return \$user;\n        }\n\n        return auth()->user();\n    }\n\n    public static function canAutoCreateAreasOnSave(): bool\n    {\n        if (request()->boolean('area_listing_admin_save')) {\n            return true;\n        }\n\n        return self::userCanAutoCreateAreas(self::resolveAreaListingActor());\n    }",

    "        if (method_exists(\$user, 'getTable') && \$user->getTable() === 'customers') {\n            return (bool) (\$user->can_manage_area_listing ?? false);\n        }" =>
        "        if (\$user instanceof Customer) {\n            return (bool) \$user->is_agent && (bool) \$user->can_manage_area_listing;\n        }\n\n        if (method_exists(\$user, 'getTable') && \$user->getTable() === 'customers') {\n            return (bool) (\$user->is_agent ?? false) && (bool) (\$user->can_manage_area_listing ?? false);\n        }",
]);

$api = $root . '/app/Plugins/AreaListing/Http/Controllers/Api/AreaListingApiController.php';
patchFile($api, [
    "use App\\Http\\Controllers\\Controller;" =>
        "use App\\Http\\Controllers\\Controller;\nuse App\\Models\\Customer;",

    "    public function permissions(Request \$request)\n    {\n        return response()->json([\n            'error' => false,\n            'message' => 'Area listing permissions fetched successfully',\n            'data' => [\n                'can_manage_area_listing' => AreaListingService::canAutoCreateAreasOnSave(),\n            ],\n        ]);\n    }" =>
        "    public function permissions(Request \$request)\n    {\n        \$actor = AreaListingService::resolveAreaListingActor();\n        if (\$actor instanceof Customer && \$actor->id) {\n            \$actor = Customer::query()->find(\$actor->id) ?? \$actor;\n        }\n        \$allowed = AreaListingService::userCanAutoCreateAreas(\$actor);\n\n        return response()->json([\n            'error' => false,\n            'message' => 'Area listing permissions fetched successfully',\n            'data' => [\n                'can_manage_area_listing' => \$allowed,\n                'can_auto_create_areas' => \$allowed,\n            ],\n        ]);\n    }",
]);

// Ensure DB column exists
try {
    require $root . '/vendor/autoload.php';
    $app = require $root . '/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    if (!Illuminate\Support\Facades\Schema::hasColumn('customers', 'can_manage_area_listing')) {
        Illuminate\Support\Facades\Schema::table('customers', function ($table) {
            $table->boolean('can_manage_area_listing')->default(false)->after('is_agent_verified');
        });
        echo "MIGRATION: added customers.can_manage_area_listing\n";
    } else {
        echo "OK: column can_manage_area_listing exists\n";
    }
} catch (Throwable $e) {
    echo "MIGRATION SKIP: " . $e->getMessage() . "\n";
}

echo "DONE\n";

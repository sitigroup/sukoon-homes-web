<?php

$path = '/www/wwwroot/admin-homes/app/Http/Controllers/Api/AuthApiController.php';
$content = file_get_contents($path);

$needle = "            \$response['data'] = new CustomerResource(\$credentials, [
                'is_agent',
                'is_user_verified',
                'is_appointment_available',
                'become_agent_status',
                'agent_verification_status',
                'user_verification_status',
            ]);
            \$response['error'] = false;
            \$response['message'] = trans('Login Successfully');";

$replace = "            \$data = (new CustomerResource(\$credentials, [
                'is_agent',
                'is_user_verified',
                'is_appointment_available',
                'become_agent_status',
                'agent_verification_status',
                'user_verification_status',
            ]))->resolve();
            if (class_exists(\\App\\Plugins\\AreaListing\\Services\\AreaListingService::class)) {
                \$data['can_manage_area_listing'] = \\App\\Plugins\\AreaListing\\Services\\AreaListingService::userCanAutoCreateAreas(\$credentials);
            } else {
                \$data['can_manage_area_listing'] = (bool) (\$credentials->is_agent && (\$credentials->can_manage_area_listing ?? false));
            }
            \$response['data'] = \$data;
            \$response['error'] = false;
            \$response['message'] = trans('Login Successfully');";

if (strpos($content, 'AreaListingService::userCanAutoCreateAreas($credentials)') !== false) {
    echo "already patched\n";
    exit(0);
}

if (strpos($content, $needle) === false) {
    echo "needle not found\n";
    exit(1);
}

file_put_contents($path, str_replace($needle, $replace, $content));
echo "AuthApiController login patched\n";

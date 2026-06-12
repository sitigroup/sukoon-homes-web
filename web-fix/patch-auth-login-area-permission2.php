<?php

$path = '/www/wwwroot/admin-homes/app/Http/Controllers/Api/AuthApiController.php';
$content = file_get_contents($path);

if (strpos($content, 'AreaListingService::userCanAutoCreateAreas($credentials)') !== false) {
    echo "already patched\n";
    exit(0);
}

$old = "\$response['data'] = new CustomerResource(\$credentials, [
                'is_agent',
                'is_user_verified',
                'is_appointment_available',
                'become_agent_status',
                'agent_verification_status',
                'user_verification_status',
            ]);";

$new = "\$loginData = (new CustomerResource(\$credentials, [
                'is_agent',
                'is_user_verified',
                'is_appointment_available',
                'become_agent_status',
                'agent_verification_status',
                'user_verification_status',
            ]))->resolve();
            if (class_exists(\\App\\Plugins\\AreaListing\\Services\\AreaListingService::class)) {
                \$loginData['can_manage_area_listing'] = \\App\\Plugins\\AreaListing\\Services\\AreaListingService::userCanAutoCreateAreas(\$credentials);
            } else {
                \$loginData['can_manage_area_listing'] = (bool) (\$credentials->is_agent && (\$credentials->can_manage_area_listing ?? false));
            }
            \$response['data'] = \$loginData;";

if (strpos($content, $old) === false) {
    echo "block not found\n";
    exit(1);
}

file_put_contents($path, str_replace($old, $new, $content));
echo "patched\n";

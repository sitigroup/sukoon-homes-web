<?php
$file = '/www/wwwroot/admin-homes/app/Http/Controllers/Api/SettingsApiController.php';
$content = file_get_contents($file);
$old = <<<'OLD'
                $user_data = User::find(1);
                $settingsData['admin_name'] = $user_data->name;
                $settingsData['admin_image'] = url('/assets/images/faces/2.jpg');
OLD;
$new = <<<'NEW'
                $user_data = User::find(1);
                $settingsData['admin_name'] = $user_data?->name ?? '';
                $settingsData['admin_image'] = $user_data
                    ? url('/assets/images/faces/2.jpg')
                    : '';
NEW;
if (!str_contains($content, $old)) {
    echo "Pattern not found\n";
    exit(1);
}
file_put_contents($file, str_replace($old, $new, $content));
echo "Patched OK\n";

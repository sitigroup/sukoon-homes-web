<?php

$path = '/www/wwwroot/admin-homes/app/Http/Controllers/CustomersController.php';
$lines = file($path);

$start = null;
for ($i = 0; $i < count($lines); $i++) {
    if (trim($lines[$i]) === '$operate = null;' && isset($lines[$i + 1]) && str_contains($lines[$i + 1], 'is_admin_added')) {
        $start = $i;
        break;
    }
}

if ($start === null) {
    echo "FAIL: operate block not found\n";
    exit(1);
}

$replacement = [
    "            \$operate = null;\n",
    "            if (function_exists('has_permissions') && has_permissions('update', 'customer')) {\n",
    "                \$operate = BootstrapTableService::editButton(route('customer.edit', \$row->id), false);\n",
    "            }\n",
    "            if (\$row->is_admin_added && function_exists('has_permissions') && has_permissions('delete', 'customer')) {\n",
    "                \$operate = (\$operate ?? '') . BootstrapTableService::deleteAjaxButton(route('customer.destroy', \$row->id), false);\n",
    "            }\n",
];

array_splice($lines, $start, 5, $replacement);
file_put_contents($path, implode('', $lines));
echo "OK: edit button enabled for all customers\n";

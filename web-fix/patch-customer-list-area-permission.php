<?php

$base = '/www/wwwroot/admin-homes';

// 1) Customer list table column (line-based insert after Agent column)
$indexPath = $base . '/resources/views/customer/index.blade.php';
$lines = file($indexPath);
$out = [];
$inserted = false;
foreach ($lines as $i => $line) {
    $out[] = $line;
    if (!$inserted && str_contains($line, "{{ __('Agent') }}") && isset($lines[$i - 2]) && str_contains($lines[$i - 2], 'data-field="is_agent"')) {
        $out[] = "                                    <th scope=\"col\" data-field=\"can_manage_area_listing\" data-sortable=\"true\"\n";
        $out[] = "                                        data-align=\"center\" data-formatter=\"areaListingPermissionFormatter\">\n";
        $out[] = "                                        {{ __('Area Wise') }}</th>\n";
        $inserted = true;
    }
}
if (!$inserted) {
    if (str_contains(implode('', $lines), 'areaListingPermissionFormatter')) {
        echo "SKIP: index column already present\n";
    } else {
        echo "FAIL: could not insert index column\n";
        exit(1);
    }
} else {
    file_put_contents($indexPath, implode('', $out));
    echo "OK: index column added\n";
}

// 2) Bootstrap table formatter
$formatterPath = $base . '/public/assets/js/custom/formatter.js';
$formatter = file_get_contents($formatterPath);
$fn = <<<'JS'

function areaListingPermissionFormatter(value, row) {
    var isAgent = row.is_agent === true || row.is_agent === 1 || row.is_agent === '1';
    if (!isAgent) {
        return '<span class="text-muted">—</span>';
    }
    var allowed = value === true || value === 1 || value === '1';
    if (allowed) {
        return '<span class="badge rounded-pill bg-success">' + (window.trans['Allowed'] || 'Allowed') + '</span>';
    }
    return '<span class="badge rounded-pill bg-secondary">' + (window.trans['Not Allowed'] || 'Not Allowed') + '</span>';
}
JS;

if (!str_contains($formatter, 'function areaListingPermissionFormatter')) {
    $anchor = "function agentBadgeFormatter(value, row) {";
    $pos = strpos($formatter, $anchor);
    if ($pos === false) {
        echo "FAIL: agentBadgeFormatter anchor not found\n";
        exit(1);
    }
    $end = strpos($formatter, "\n}\n", $pos);
    if ($end === false) {
        echo "FAIL: end of agentBadgeFormatter not found\n";
        exit(1);
    }
    $end += strlen("\n}\n");
    $formatter = substr($formatter, 0, $end) . $fn . substr($formatter, $end);
    file_put_contents($formatterPath, $formatter);
    echo "OK: formatter added\n";
} else {
    echo "SKIP: formatter already present\n";
}

// 3) Explicit API field for bootstrap table
$ctrlPath = $base . '/app/Http/Controllers/CustomersController.php';
$ctrl = file_get_contents($ctrlPath);
$lineNeedle = "            \$tempRow['is_agent_verified'] = \$row->is_agent_verified;\n";
$lineInsert = $lineNeedle . "            \$tempRow['can_manage_area_listing'] = \$row->is_agent ? (int) (\$row->can_manage_area_listing ?? 0) : null;\n";

if (!str_contains($ctrl, "can_manage_area_listing'] = \$row->is_agent")) {
    if (!str_contains($ctrl, $lineNeedle)) {
        echo "FAIL: customerList row anchor not found\n";
        exit(1);
    }
    $ctrl = str_replace($lineNeedle, $lineInsert, $ctrl);
    file_put_contents($ctrlPath, $ctrl);
    echo "OK: customerList field added\n";
} else {
    echo "SKIP: customerList field already present\n";
}

echo "DONE\n";

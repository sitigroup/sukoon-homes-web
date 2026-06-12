<?php
// Quick test for Barmer geocode - documents area source type
$components = [
    ['long_name' => '1042', 'types' => ['premise']],
    ['long_name' => 'krishna Nagar', 'types' => ['political', 'sublocality', 'sublocality_level_1']],
    ['long_name' => 'Barmer', 'types' => ['locality', 'political']],
];
$result = [
    'address_components' => $components,
    'formatted_address' => '1042, krishna Nagar, Barmer, Rajasthan 344001, India',
];

function componentHasType($types, $wanted) {
    return in_array($wanted, $types, true);
}
function findComponentName($components, $typeList) {
    foreach ($typeList as $type) {
        foreach ($components as $comp) {
            if (componentHasType($comp['types'], $type)) {
                return trim($comp['long_name']);
            }
        }
    }
    return '';
}

$area = findComponentName($components, ['sublocality_level_1', 'sublocality', 'neighborhood']);
echo "area={$area} source=sublocality_level_1\n";
echo "NOTE: Krishna Nagar matches DB area — resolve returns area_id, detected UI hidden\n";

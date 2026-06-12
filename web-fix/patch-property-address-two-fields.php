<?php

$base = '/www/wwwroot/admin-homes';

$editOld = <<<'BLADE'
                                {{-- Client Address --}}
                                <div class="col-md-12 col-12 form-group mandatory">
                                    {{ Form::label('address', __('Client Address'), ['class' => 'form-label col-12 ']) }}
                                    {{ Form::textarea('client_address', isset($list->client_address) ? $list->client_address : (system_setting('company_address') ?? ""), ['class' => 'form-control ', 'placeholder' => trans('Client Address'), 'rows' => '4', 'id' => 'client-address', 'autocomplete' => 'off', 'required' => 'true']) }}
                                </div>

                                {{-- Address --}}
                                <div class="col-md-12 col-12 form-group mandatory">
                                    {{ Form::label('address', __('Address'), ['class' => 'form-label col-12 ']) }}
                                    {{ Form::textarea('address', isset($list->address) ? $list->address : '', ['class' => 'form-control ', 'placeholder' => trans('Address'), 'rows' => '4', 'id' => 'address', 'autocomplete' => 'off', 'required' => 'true']) }}
                                </div>
BLADE;

$editNew = file_get_contents(__DIR__ . '/property-edit.blade.php');
preg_match('/\{\{-- Address by Google.*?<\/div>\s*<\/div>\s*<\/div>/s', $editNew, $m);
// use web-fix file content for replacement block
$editNewBlock = <<<'BLADE'
                                {{-- Address by Google (map / GPS) --}}
                                <div class="col-md-12 col-12 form-group mandatory">
                                    {{ Form::label('address', __('By Google'), ['class' => 'form-label col-12 ']) }}
                                    {{ Form::textarea('address', isset($list->address) ? $list->address : '', ['class' => 'form-control ', 'placeholder' => __('Address from map / GPS'), 'rows' => '4', 'id' => 'address', 'autocomplete' => 'off', 'required' => 'true']) }}
                                    <div class="form-text text-muted">{{ __('Filled automatically when you move the map pin or use Get Current Location.') }}</div>
                                </div>

                                {{-- Address by customer --}}
                                <div class="col-md-12 col-12 form-group mandatory">
                                    {{ Form::label('client_address', __('By Customer'), ['class' => 'form-label col-12 ']) }}
                                    {{ Form::textarea('client_address', isset($list->client_address) ? $list->client_address : '', ['class' => 'form-control ', 'placeholder' => __('Address as told by the customer'), 'rows' => '4', 'id' => 'client-address', 'autocomplete' => 'off', 'required' => 'true']) }}
                                </div>
BLADE;

$files = [
    $base . '/resources/views/property/edit.blade.php' => [$editOld, $editNewBlock],
];

$createOld = <<<'BLADE'
                                <div class="col-md-12 col-12 form-group mandatory">
                                    {{ Form::label('address', __('Client Address'), ['class' => 'form-label col-12 ']) }}
                                    {{ Form::textarea('client_address', system_setting('company_address') ?? "", [
                                        'class' => 'form-control ',
                                        'placeholder' => __('Client Address'),
                                        'rows' => '4',
                                        'id' => 'client-address',
                                        'autocomplete' => 'off',
                                        'required' => 'true',
                                    ]) }}
                                </div>
                                <div class="col-md-12 col-12 form-group mandatory">
                                    {{ Form::label('address', __('Address'), ['class' => 'form-label col-12 ']) }}
                                    {{ Form::textarea('address', '', [
                                        'class' => 'form-control ',
                                        'placeholder' => __('Address'),
                                        'rows' => '4',
                                        'id' => 'address',
                                        'autocomplete' => 'off',
                                        'required' => 'true',
                                    ]) }}
                                </div>
BLADE;

$createNew = file_get_contents(__DIR__ . '/create.blade.php');
preg_match('/col-md-12 col-12 form-group mandatory">\s*\{\{ Form::label\(\'address\', __\(\'By Google\'\).*?client-address.*?<\/div>\s*<\/div>/s', $createNew, $cm);
$createNewBlock = <<<'BLADE'
                                <div class="col-md-12 col-12 form-group mandatory">
                                    {{ Form::label('address', __('By Google'), ['class' => 'form-label col-12 ']) }}
                                    {{ Form::textarea('address', '', [
                                        'class' => 'form-control ',
                                        'placeholder' => __('Address from map / GPS'),
                                        'rows' => '4',
                                        'id' => 'address',
                                        'autocomplete' => 'off',
                                        'required' => 'true',
                                    ]) }}
                                    <div class="form-text text-muted">{{ __('Filled automatically when you move the map pin or use Get Current Location.') }}</div>
                                </div>
                                <div class="col-md-12 col-12 form-group mandatory">
                                    {{ Form::label('client_address', __('By Customer'), ['class' => 'form-label col-12 ']) }}
                                    {{ Form::textarea('client_address', '', [
                                        'class' => 'form-control ',
                                        'placeholder' => __('Address as told by the customer'),
                                        'rows' => '4',
                                        'id' => 'client-address',
                                        'autocomplete' => 'off',
                                        'required' => 'true',
                                    ]) }}
                                </div>
BLADE;

$files[$base . '/resources/views/property/create.blade.php'] = [$createOld, $createNewBlock];

$partialPath = $base . '/app/Plugins/AreaListing/views/admin/partials/listing-location-fields.blade.php';
$partial = file_get_contents($partialPath);
$partial = preg_replace(
    '/<div class="col-md-12 form-group">\s*<label class="form-label col-12">\{\{ __\(\'Customer \/ Manual Address\'\) \}\}<\/label>.*?<\/textarea>\s*<\/div>\s*/s',
    '',
    $partial,
    1,
    $count
);
if ($count) {
    file_put_contents($partialPath, $partial);
    echo "OK: removed manual_address from listing-location-fields\n";
} else {
    echo "SKIP or already removed: listing-location-fields\n";
}

foreach ($files as $path => [$old, $new]) {
    $content = file_get_contents($path);
    if (strpos($content, $old) === false) {
        if (strpos($content, 'By Google') !== false) {
            echo "OK already: " . basename($path) . "\n";
            continue;
        }
        echo "FAIL pattern: " . basename($path) . "\n";
        continue;
    }
    file_put_contents($path, str_replace($old, $new, $content));
    echo "OK: " . basename($path) . "\n";
}

copy(__DIR__ . '/AreaListingService.php', $base . '/app/Plugins/AreaListing/Services/AreaListingService.php');
echo "OK: AreaListingService.php\n";

passthru('cd ' . escapeshellarg($base) . ' && php artisan view:clear 2>&1');

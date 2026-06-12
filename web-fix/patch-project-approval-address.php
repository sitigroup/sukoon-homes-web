<?php
/**
 * Fix customer manual address on project save + show on admin approval modal.
 */
$servicePath = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Services/AreaListingService.php';
copy(__DIR__ . '/AreaListingService.php', $servicePath);
echo "deployed AreaListingService.php\n";

$controllerPath = '/www/wwwroot/admin-homes/app/Http/Controllers/ProjectController.php';
$c = file_get_contents($controllerPath);
$needle = "\$tempRow['raw_document_action'] = \$documentAction;";
$insert = <<<'PHP'
$tempRow['raw_document_action'] = $documentAction;
            $projectLocation = \Illuminate\Support\Facades\DB::table('area_listing_project_locations')
                ->where('project_id', $row->id)
                ->first();
            $tempRow['listing_google_address'] = $projectLocation->full_address ?? ($row->location ?? '');
            $tempRow['listing_client_address'] = $projectLocation->manual_address ?? '';
            $tempRow['listing_area'] = $projectLocation->area_name ?? $projectLocation->detected_area_name ?? '';
            $tempRow['listing_sub_area'] = $projectLocation->sub_area_name ?? $projectLocation->detected_sub_area_name ?? '';
PHP;
if (str_contains($c, 'listing_client_address')) {
    echo "ProjectController listing fields ok\n";
} elseif (str_contains($c, $needle)) {
    $c = str_replace($needle, $insert, $c);
    file_put_contents($controllerPath, $c);
    echo "patched ProjectController show()\n";
} else {
    echo "WARN: ProjectController needle not found\n";
}

$indexPath = '/www/wwwroot/admin-homes/resources/views/project/index.blade.php';
$index = file_get_contents($indexPath);
if (str_contains($index, 'id="project-approval-location-review"')) {
    echo "index modal location review ok\n";
} else {
    $modalNeedle = '{!! Form::hidden(\'id\', \'\', [\'id\' => \'edit-request-status-id\']) !!}';
    $modalInsert = <<<'BLADE'
{!! Form::hidden('id', '', ['id' => 'edit-request-status-id']) !!}
                        <div id="project-approval-location-review" class="col-12 mb-3 p-3 border rounded bg-light" style="display:none">
                            <h6 class="mb-2">{{ __('Location (from listing)') }}</h6>
                            <p class="mb-1"><strong>{{ __('Area') }}:</strong> <span id="review-listing-area">—</span></p>
                            <p class="mb-1"><strong>{{ __('Sub Area') }}:</strong> <span id="review-listing-sub-area">—</span></p>
                            <p class="mb-1"><strong>{{ __('By Google') }}:</strong><br><span id="review-listing-google-address" class="text-muted">—</span></p>
                            <p class="mb-0"><strong>{{ __('By Customer') }}:</strong><br><span id="review-listing-client-address" class="text-muted">—</span></p>
                        </div>
BLADE;
    if (str_contains($index, $modalNeedle)) {
        $index = str_replace($modalNeedle, $modalInsert, $index);
    }

    $jsNeedle = "$('#changeRequestStatusModal').modal('show');";
    $jsInsert = <<<'JS'
$('#review-listing-area').text(row.listing_area || '—');
                    $('#review-listing-sub-area').text(row.listing_sub_area || '—');
                    $('#review-listing-google-address').text(row.listing_google_address || row.location || '—');
                    $('#review-listing-client-address').text(row.listing_client_address || '—');
                    $('#project-approval-location-review').show();
                    $('#changeRequestStatusModal').modal('show');
JS;
    if (str_contains($index, $jsNeedle) && ! str_contains($index, 'review-listing-client-address')) {
        $index = str_replace($jsNeedle, $jsInsert, $index);
    }

    file_put_contents($indexPath, $index);
    echo "patched project index approval modal\n";
}

passthru('cd /www/wwwroot/admin-homes && php -l app/Plugins/AreaListing/Services/AreaListingService.php 2>&1');
passthru('cd /www/wwwroot/admin-homes && php -l app/Http/Controllers/ProjectController.php 2>&1');
passthru('cd /www/wwwroot/admin-homes && php artisan optimize:clear 2>&1 | tail -2');

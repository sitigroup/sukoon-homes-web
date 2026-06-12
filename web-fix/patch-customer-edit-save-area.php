<?php

$blade = '/www/wwwroot/admin-homes/resources/views/customer/edit.blade.php';
$controller = '/www/wwwroot/admin-homes/app/Http/Controllers/CustomersController.php';

$bladeContent = file_get_contents($blade);

$insertBlock = <<<'BLADE'
					@if($customer->is_agent)
					<div class="col-12 mb-3">
						<label class="form-label fw-bold">{{ __('Area Wise — direct create') }}</label>
						<input type="hidden" name="can_manage_area_listing" value="0">
						<div class="form-check form-switch mt-2">
							<input class="form-check-input" type="checkbox" name="can_manage_area_listing" id="can_manage_area_listing" value="1" @checked((bool) ($customer->can_manage_area_listing ?? false))>
							<label class="form-check-label" for="can_manage_area_listing">{{ __('Allow this agent to add Area/Sub Area without admin approval (same as admin panel)') }}</label>
						</div>
						<small class="text-muted d-block">{{ __('Other agents still submit suggestions for approval. Saved with the button below.') }}</small>
					</div>
					@endif

BLADE;

$needle = "					<div class=\"d-flex justify-content-end gap-2\">\n\t\t\t\t\t\t<button type=\"submit\" class=\"btn btn-primary\">{{ __('Save') }}</button>";
if (! str_contains($bladeContent, $needle) || str_contains($bladeContent, 'Saved with the button below')) {
    echo "SKIP or already patched blade\n";
} else {
    $bladeContent = str_replace($needle, $insertBlock . $needle, $bladeContent);
}

$removeBlock = <<<'BLADE'
					<div class="col-md-4 mb-3">
						<label class="form-label fw-bold">{{ __('Area Wise — direct create') }}</label>
						<div class="form-check form-switch mt-2">
							<input class="form-check-input" type="checkbox" name="can_manage_area_listing" id="can_manage_area_listing" value="1" @checked((bool) ($customer->can_manage_area_listing ?? false))>
							<label class="form-check-label" for="can_manage_area_listing">{{ __('Allow this agent to add Area/Sub Area without admin approval (same as admin panel)') }}</label>
						</div>
						<small class="text-muted">{{ __('Other agents still submit suggestions for approval.') }}</small>
					</div>

BLADE;

$bladeContent = str_replace($removeBlock, '', $bladeContent);
file_put_contents($blade, $bladeContent);
echo "PATCHED blade\n";

$ctrl = file_get_contents($controller);
$ctrl = str_replace(
    "if (\$customer->is_agent && \$request->has('can_manage_area_listing')) {\n                \$updateData['can_manage_area_listing'] = \$request->boolean('can_manage_area_listing') ? 1 : 0;\n            }",
    "if (\$customer->is_agent) {\n                \$updateData['can_manage_area_listing'] = \$request->boolean('can_manage_area_listing') ? 1 : 0;\n            }",
    $ctrl,
    $count
);
echo "controller replacements: {$count}\n";
file_put_contents($controller, $ctrl);
echo "PATCHED controller\n";

<?php

$root = '/www/wwwroot/admin-homes';
$controller = $root . '/app/Http/Controllers/CustomersController.php';
$blade = $root . '/resources/views/customer/edit.blade.php';
$routes = $root . '/routes/web.php';

// --- Controller: fix email duplicate check + new method ---
$ctrl = file_get_contents($controller);

if (! str_contains($ctrl, 'function updateAreaListingPermission')) {
    $insertBefore = "    public function customerList(Request \$request)";
    $method = <<<'PHP'

    public function updateAreaListingPermission(Request $request, $id)
    {
        if (! has_permissions('update', 'customer')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }

        $customer = Customer::findOrFail($id);
        if (! $customer->is_agent) {
            ResponseService::errorResponse(__('This customer is not an agent.'));
        }

        $request->validate([
            'can_manage_area_listing' => 'nullable|boolean',
        ]);

        $customer->update([
            'can_manage_area_listing' => $request->boolean('can_manage_area_listing') ? 1 : 0,
        ]);

        ResponseService::successResponse(__('Area Wise permission saved successfully.'));
    }

PHP;
    $ctrl = str_replace($insertBefore, $method . $insertBefore, $ctrl);
}

$ctrl = str_replace(
    "            // Check for email uniqueness among email-login customers excluding current\n            \$exists = Customer::where(['email' => \$request->email, 'logintype' => 3])\n                ->where('id', '!=', \$customer->id)\n                ->count();\n            if (\$exists) {\n                ResponseService::errorResponse('User Already Exists');\n            }",
    "            // Only validate email uniqueness when it actually changed\n            \$incomingEmail = trim((string) \$request->email);\n            \$currentEmail = trim((string) (\$customer->getRawOriginal('email') ?? ''));\n            if (strcasecmp(\$incomingEmail, \$currentEmail) !== 0) {\n                \$exists = Customer::where('email', \$incomingEmail)\n                    ->where('id', '!=', \$customer->id)\n                    ->exists();\n                if (\$exists) {\n                    ResponseService::errorResponse('User Already Exists');\n                }\n            }",
    $ctrl
);

$ctrl = str_replace(
    "                'can_manage_area_listing' => 'nullable|boolean',\n",
    "",
    $ctrl
);

$ctrl = preg_replace(
    '/\s*if \(\$customer->is_agent\) \{\s*\$updateData\[\'can_manage_area_listing\'\] = \$request->boolean\(\'can_manage_area_listing\'\) \? 1 : 0;\s*\}\s*/',
    "\n",
    $ctrl,
    2
);

file_put_contents($controller, $ctrl);
echo "PATCHED controller\n";

// --- Routes ---
$web = file_get_contents($routes);
$routeLine = "        Route::put('customer/{id}/area-listing-permission', [CustomersController::class, 'updateAreaListingPermission'])->name('customer.area-listing-permission');\n";
if (! str_contains($web, 'area-listing-permission')) {
    $web = str_replace(
        "        Route::resource('customer', CustomersController::class);",
        "        Route::resource('customer', CustomersController::class);\n" . $routeLine,
        $web
    );
    file_put_contents($routes, $web);
    echo "PATCHED routes\n";
}

// --- Blade ---
$bladeContent = file_get_contents($blade);

$areaBlockInMain = <<<'BLADE'
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

$bladeContent = str_replace($areaBlockInMain, '', $bladeContent);

$agentCardForm = <<<'BLADE'
				<form action="{{ route('customer.area-listing-permission', $customer->id) }}" method="POST" class="create-form area-listing-permission-form" data-success-function="successForm">
					@csrf
					@method('PUT')
					<div class="row">
						<div class="col-md-12 mb-3">
							<label class="form-label fw-bold">{{ __('Area Wise — direct create') }}</label>
							<input type="hidden" name="can_manage_area_listing" value="0">
							<div class="form-check form-switch mt-2">
								<input class="form-check-input" type="checkbox" name="can_manage_area_listing" id="can_manage_area_listing" value="1" @checked((bool) ($customer->can_manage_area_listing ?? false))>
								<label class="form-check-label" for="can_manage_area_listing">{{ __('Allow this agent to add Area/Sub Area without admin approval (same as admin panel)') }}</label>
							</div>
							<small class="text-muted d-block">{{ __('Other agents still submit suggestions for approval.') }}</small>
						</div>
					</div>
					<div class="d-flex justify-content-end gap-2">
						<button type="submit" class="btn btn-primary">{{ __('Save Area Wise Setting') }}</button>
					</div>
				</form>
				<hr class="my-4">
BLADE;

$marker = '<div class="card-body mt-4">' . "\n\t\t\t\t<div class=\"row\">";
$agentStart = strpos($bladeContent, '<h4>{{ __(\'Agent Profile\') }}</h4>');
if ($agentStart !== false) {
    $bodyPos = strpos($bladeContent, '<div class="card-body mt-4">', $agentStart);
    if ($bodyPos !== false) {
        $afterBody = $bodyPos + strlen('<div class="card-body mt-4">');
        if (! str_contains(substr($bladeContent, $afterBody, 200), 'area-listing-permission-form')) {
            $bladeContent = substr($bladeContent, 0, $afterBody) . "\n" . $agentCardForm . substr($bladeContent, $afterBody);
        }
    }
}

file_put_contents($blade, $bladeContent);
echo "PATCHED blade\n";
echo "Done.\n";

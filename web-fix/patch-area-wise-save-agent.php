<?php

$path = '/www/wwwroot/admin-homes/app/Http/Controllers/CustomersController.php';
$content = file_get_contents($path);

$old = <<<'PHP'
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
PHP;

$new = <<<'PHP'
        $customer = Customer::findOrFail($id);

        $request->validate([
            'can_manage_area_listing' => 'nullable|boolean',
        ]);

        $enableAreaWise = $request->boolean('can_manage_area_listing');
        $updates = [
            'can_manage_area_listing' => $enableAreaWise ? 1 : 0,
        ];
        if ($enableAreaWise && ! $customer->is_agent) {
            $updates['is_agent'] = 1;
        }

        $customer->update($updates);
PHP;

if (!str_contains($content, $old)) {
    echo "SKIP: already patched or anchor changed\n";
    exit(0);
}

file_put_contents($path, str_replace($old, $new, $content));
echo "OK: Area Wise save also marks customer as agent when enabling\n";

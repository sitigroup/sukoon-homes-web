<?php
/**
 * Add CustomersController::show() — resource route /customer/{id} was 500 (method missing).
 * show() redirects to customer.edit (no separate show view in wrteam admin).
 *
 * Run on server after backup:
 *   php web-fix/patch-customer-show.php
 */
$root = '/www/wwwroot/admin-homes';
$path = $root . '/app/Http/Controllers/CustomersController.php';

if (! is_file($path)) {
    fwrite(STDERR, "Missing: {$path}\n");
    exit(1);
}

$src = file_get_contents($path);
if (str_contains($src, 'public function show(')) {
    echo "Already patched.\n";
    exit(0);
}

$needle = "        return view('customer.index');\n    }\n";
$insert = <<<'PHP'
        return view('customer.index');
    }

    /**
     * Resource route expects show; admin uses edit for customer detail.
     */
    public function show($id)
    {
        if (! has_permissions('update', 'customer')) {
            return redirect()->back()->with('error', trans(PERMISSION_ERROR_MSG));
        }

        return redirect()->route('customer.edit', $id);
    }

PHP;

if (! str_contains($src, $needle)) {
    fwrite(STDERR, "Anchor not found — patch manually.\n");
    exit(1);
}

file_put_contents($path, str_replace($needle, $insert, $src, $count));
echo $count ? "Patched CustomersController::show()\n" : "No change.\n";

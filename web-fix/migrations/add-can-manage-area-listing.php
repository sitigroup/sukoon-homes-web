<?php

/**
 * Run on server: php /www/wwwroot/admin-homes/web-fix/migrations/add-can-manage-area-listing.php
 */

$root = dirname(__DIR__, 3);
if (! is_file($root . '/vendor/autoload.php')) {
    $root = '/www/wwwroot/admin-homes';
}

require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (Schema::hasColumn('customers', 'can_manage_area_listing')) {
    echo "OK: customers.can_manage_area_listing already exists\n";
    exit(0);
}

DB::statement('ALTER TABLE customers ADD COLUMN can_manage_area_listing TINYINT(1) NOT NULL DEFAULT 0 AFTER is_agent_verified');
echo "OK: added customers.can_manage_area_listing\n";

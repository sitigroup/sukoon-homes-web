#!/usr/bin/env bash
set -e
cd /www/wwwroot/admin-homes
php artisan tinker --execute='''foreach (DB::select("SHOW TABLES LIKE \"wa_%\"") as $row) { foreach ((array)$row as $name) { echo $name . PHP_EOL; } }'''
<?php
/**
 * Safely deactivate junk city 28 + area 72 via Eloquent (not raw SQL).
 */
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\AreaListing\Models\Area;
use App\Plugins\AreaListing\Models\City;
use App\Plugins\AreaListing\Support\AreaWorkflowState;

$out = ['before' => [], 'after' => []];

foreach ([28 => City::class, 72 => Area::class] as $id => $model) {
    $row = $model::find($id);
    $out['before'][$id] = $row ? [
        'type' => class_basename($model),
        'name' => $row->name ?? null,
        'slug' => $row->slug ?? null,
        'status' => $row->status ?? null,
        'workflow_status' => $row->workflow_status ?? null,
    ] : null;
}

$city = City::find(28);
if ($city) {
    $city->status = false;
    $city->save();
}

$area = Area::find(72);
if ($area) {
    $area->forceFill(AreaWorkflowState::archivePayload())->save();
}

foreach ([28 => City::class, 72 => Area::class] as $id => $model) {
    $row = $model::find($id);
    $out['after'][$id] = $row ? [
        'type' => class_basename($model),
        'name' => $row->name ?? null,
        'slug' => $row->slug ?? null,
        'status' => (bool) ($row->status ?? false),
        'workflow_status' => $row->workflow_status ?? null,
        'archived_at' => optional($row->archived_at ?? null)->toDateTimeString(),
    ] : null;
}

echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;

<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = DB::table('area_listing_project_locations')
    ->where('area_name', 'like', '%Chandan%')
    ->orWhere('detected_area_name', 'like', '%Chandan%')
    ->orWhere('sub_area_name', 'like', '%New Road%')
    ->orWhere('detected_sub_area_name', 'like', '%New Road%')
    ->get();

foreach ($rows as $l) {
    $p = DB::table('projects')->where('id', $l->project_id)->first();
    echo "project {$l->project_id} slug={$p->slug_id} status={$p->request_status} area_id={$l->area_id} sub_id={$l->sub_area_id}\n";
    echo "  {$l->area_name} / {$l->sub_area_name}\n";
}

echo "\nSuggestions Chandan/New:\n";
foreach (DB::table('area_listing_suggestions')
    ->where(function ($q) {
        $q->where('name', 'like', '%Chandan%')->orWhere('name', 'like', '%New Road%');
    })->get() as $s) {
    echo "{$s->id} {$s->status} {$s->type} {$s->name}\n";
}

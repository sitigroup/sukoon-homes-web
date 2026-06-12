<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$p = DB::table('projects')->where('title', 'like', '%iuiuiuiu%')->first();
if (!$p) {
    echo "project not found\n";
    exit;
}
echo "project id={$p->id} title={$p->title} status={$p->request_status}\n";
$l = DB::table('area_listing_project_locations')->where('project_id', $p->id)->first();
if ($l) {
    echo "location: area_id={$l->area_id} sub={$l->sub_area_id}\n";
    echo "  area_name={$l->area_name} detected_area={$l->detected_area_name}\n";
    echo "  sub_name={$l->sub_area_name} detected_sub={$l->detected_sub_area_name}\n";
    echo "  updated_at={$l->updated_at}\n";
} else {
    echo "no location row\n";
}

echo "\nRecent pending suggestions:\n";
foreach (DB::table('area_listing_suggestions')->where('status', 'pending')->orderByDesc('id')->limit(10)->get() as $s) {
    echo "  #{$s->id} {$s->type} {$s->name} city={$s->city}\n";
}

echo "\nRecent suggestions any status (last 10):\n";
foreach (DB::table('area_listing_suggestions')->orderByDesc('id')->limit(10)->get() as $s) {
    echo "  #{$s->id} {$s->status} {$s->type} {$s->name}\n";
}

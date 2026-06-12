<?php
/**
 * Probe junk path sources: AreaListing hierarchy vs property location rows.
 */
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$junkPatterns = ['baldev-nagar-barmer', 'raj-colneyer', 'saadsd'];

echo "=== area_listing_cities (status=1) ===\n";
$cities = DB::table('area_listing_cities')->where('status', 1)->orderBy('id')->get(['id', 'name', 'slug']);
foreach ($cities as $c) {
    $flag = '';
    foreach ($junkPatterns as $j) {
        if (stripos($c->slug, $j) !== false || stripos($c->name, $j) !== false) {
            $flag = ' *** JUNK';
        }
    }
    echo "{$c->id}\t{$c->slug}\t{$c->name}{$flag}\n";
}

echo "\n=== area_listing_areas (status=1) with junk slug/name ===\n";
$areas = DB::table('area_listing_areas')->where('status', 1)->get(['id', 'city_id', 'name', 'slug']);
foreach ($areas as $a) {
    foreach ($junkPatterns as $j) {
        if (stripos($a->slug ?? '', $j) !== false || stripos($a->name, $j) !== false) {
            echo "{$a->id}\tcity={$a->city_id}\t{$a->slug}\t{$a->name}\n";
        }
    }
}

echo "\n=== area_listing_sub_areas with junk ===\n";
$subs = DB::table('area_listing_sub_areas')->get(['id', 'area_id', 'name', 'slug']);
foreach ($subs as $s) {
    foreach ($junkPatterns as $j) {
        if (stripos($s->slug ?? '', $j) !== false || stripos($s->name, $j) !== false) {
            echo "{$s->id}\tarea={$s->area_id}\t{$s->slug}\t{$s->name}\n";
        }
    }
}

echo "\n=== area_listing_property_locations pointing at junk city/area ===\n";
$locs = DB::table('area_listing_property_locations as apl')
    ->join('propertys as p', 'p.id', '=', 'apl.property_id')
    ->leftJoin('area_listing_cities as c', 'c.id', '=', 'apl.city_id')
    ->leftJoin('area_listing_areas as a', 'a.id', '=', 'apl.area_id')
    ->leftJoin('area_listing_sub_areas as sa', 'sa.id', '=', 'apl.sub_area_id')
    ->where('p.status', 1)
    ->select([
        'apl.property_id', 'apl.city_id', 'apl.area_id', 'apl.sub_area_id',
        'c.slug as city_slug', 'c.name as city_name', 'c.status as city_status',
        'a.slug as area_slug', 'a.name as area_name', 'a.status as area_status',
        'sa.slug as sub_slug', 'sa.name as sub_name',
        'p.title', 'p.city as property_city_field',
    ])
    ->get();
foreach ($locs as $l) {
    $hit = false;
    foreach ($junkPatterns as $j) {
        if (stripos($l->city_slug ?? '', $j) !== false || stripos($l->area_slug ?? '', $j) !== false
            || stripos($l->sub_slug ?? '', $j) !== false || stripos($l->property_city_field ?? '', $j) !== false) {
            $hit = true;
        }
    }
    if ($hit) {
        echo "prop={$l->property_id}\tcity_id={$l->city_id} ({$l->city_slug}, status={$l->city_status})\tarea={$l->area_name}\tsub={$l->sub_name}\tp.city={$l->property_city_field}\t{$l->title}\n";
    }
}

echo "\n=== propertys.city free-text field (active rent) ===\n";
$props = DB::table('propertys')->where('status', 1)->where('propery_type', 1)->get(['id', 'title', 'city', 'state']);
foreach ($props as $p) {
    echo "{$p->id}\t{$p->city}\t{$p->title}\n";
}

echo "\n=== city 28 detail ===\n";
$areas28 = DB::table('area_listing_areas')->where('city_id', 28)->get(['id', 'name', 'slug', 'status']);
echo 'areas under city 28: ' . count($areas28) . "\n";
foreach ($areas28 as $a) {
    echo "  {$a->id} status={$a->status} {$a->slug} {$a->name}\n";
}
echo 'property_locs city_id=28: ' . DB::table('area_listing_property_locations')->where('city_id', 28)->count() . "\n";
echo 'property_locs city_id=22: ' . DB::table('area_listing_property_locations')->where('city_id', 22)->count() . "\n";

echo "\n=== all area_listing_property_locations (active rent) ===\n";
$allLocs = DB::table('area_listing_property_locations as apl')
    ->join('propertys as p', 'p.id', '=', 'apl.property_id')
    ->leftJoin('area_listing_cities as c', 'c.id', '=', 'apl.city_id')
    ->leftJoin('area_listing_areas as a', 'a.id', '=', 'apl.area_id')
    ->where('p.status', 1)->where('p.propery_type', 1)
    ->select(['apl.property_id', 'apl.city_id', 'c.name as city_name', 'c.slug as city_slug', 'apl.area_id', 'a.name as area_name', 'p.title'])
    ->get();
foreach ($allLocs as $l) {
    echo "prop={$l->property_id}\tcity_id={$l->city_id} ({$l->city_name})\tarea_id={$l->area_id} ({$l->area_name})\t{$l->title}\n";
}

echo "\n=== duplicate property location rows ===\n";
$dupes = DB::table('area_listing_property_locations')->select('property_id', DB::raw('COUNT(*) c'))->groupBy('property_id')->having('c', '>', 1)->get();
foreach ($dupes as $d) {
    echo "prop {$d->property_id} has {$d->c} rows\n";
    $rows = DB::table('area_listing_property_locations as apl')
        ->leftJoin('area_listing_cities as c', 'c.id', '=', 'apl.city_id')
        ->where('apl.property_id', $d->property_id)
        ->get(['apl.city_id', 'c.name as city_name', 'apl.area_id']);
    foreach ($rows as $r) {
        echo "  city_id={$r->city_id} ({$r->city_name}) area={$r->area_id}\n";
    }
}

echo "\n=== shouldGenerateCity simulation ===\n";
$cities = DB::table('area_listing_cities')->where('status', 1)->get(['id', 'slug', 'name']);
foreach ($cities as $city) {
    $skipSuffix = false;
    foreach ($cities as $other) {
        if ($other->id == $city->id) continue;
        $suffix = '-' . $other->slug;
        if ($suffix !== '-' && str_ends_with($city->slug, $suffix)) {
            $skipSuffix = true;
        }
    }
    $hasLocs = DB::table('area_listing_property_locations as apl')
        ->join('propertys as p', 'p.id', '=', 'apl.property_id')
        ->where('apl.city_id', $city->id)
        ->where('p.status', 1)->where('p.request_status', 'approved')->where('p.propery_type', 1)
        ->exists();
    echo "city {$city->id} {$city->slug}: skipSuffix=" . ($skipSuffix ? 'Y' : 'N') . " hasLocs=" . ($hasLocs ? 'Y' : 'N') . " generate=" . ((!$skipSuffix && $hasLocs) ? 'Y' : 'N') . "\n";
}

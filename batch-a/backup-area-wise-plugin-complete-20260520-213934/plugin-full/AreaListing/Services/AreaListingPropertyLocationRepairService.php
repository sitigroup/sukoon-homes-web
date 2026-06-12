<?php

namespace App\Plugins\AreaListing\Services;

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AreaListingPropertyLocationRepairService
{
    public static function logLine(string $message): void
    {
        $path = storage_path('logs/repair_area_listing_links.log');
        file_put_contents($path, '[' . now()->toDateTimeString() . '] ' . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    protected static function baseMissingQuery()
    {
        $propertyTable = (new Property())->getTable();

        return Property::query()->whereNotExists(function ($q) use ($propertyTable) {
            $q->select(DB::raw(1))
                ->from('area_listing_property_locations')
                ->whereColumn('area_listing_property_locations.property_id', $propertyTable . '.id');
        });
    }

    /**
     * @return array{scanned_total: int, missing_count: int, rows_to_create: int, sample_property_ids: array<int, int>}
     */
    public static function collectStats(): array
    {
        $scanned = Property::query()->count();
        $missing = (int) self::baseMissingQuery()->count();
        $sampleIds = self::baseMissingQuery()
            ->orderBy('id')
            ->limit(25)
            ->pluck('id')
            ->all();

        return [
            'scanned_total' => $scanned,
            'missing_count' => $missing,
            'rows_to_create' => $missing,
            'sample_property_ids' => $sampleIds,
        ];
    }

    public static function dryRun(): array
    {
        $stats = self::collectStats();
        self::logLine('dry_run scanned_total=' . $stats['scanned_total'] . ' missing_count=' . $stats['missing_count'] . ' rows_to_create=' . $stats['rows_to_create']);

        return $stats;
    }

    /**
     * @return array{scanned_total: int, missing_count: int, rows_to_create: int, sample_property_ids: array<int, int>, created: int, errors: array<int, array{property_id: int, message: string}>}
     */
    public static function execute(): array
    {
        $stats = self::collectStats();
        $created = 0;
        $errors = [];

        $ids = self::baseMissingQuery()->orderBy('id')->pluck('id');

        foreach ($ids as $id) {
            try {
                $property = Property::find($id);
                if (! $property) {
                    continue;
                }

                AreaListingService::savePropertyLocation($property, Request::create('/', 'POST', []));
                $created++;
            } catch (\Throwable $e) {
                $errors[] = [
                    'property_id' => (int) $id,
                    'message' => $e->getMessage(),
                ];
            }
        }

        self::logLine('execute missing_before=' . $stats['missing_count'] . ' created=' . $created . ' errors=' . count($errors));

        return array_merge($stats, [
            'created' => $created,
            'errors' => $errors,
        ]);
    }
}

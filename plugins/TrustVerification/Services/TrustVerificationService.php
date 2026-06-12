<?php

namespace App\Plugins\TrustVerification\Services;

use App\Models\Customer;
use App\Plugins\TrustVerification\Models\TvCheckItem;
use App\Plugins\TrustVerification\Models\TvCity;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvPackage;
use App\Plugins\TrustVerification\Models\TvPackagePriceLog;
use App\Plugins\TrustVerification\Models\TvReport;
use App\Plugins\TrustVerification\Models\TvSubject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrustVerificationService
{
    public const CITY_BARMER = 'barmer';

    public const REPORT_DISK = 'local';

    /** Reject placeholder/tiny files (E2E used a ~44 byte stub that is not a readable PDF). */
    public const MIN_REPORT_PDF_BYTES = 1024;

    /** Known city slug => display label (extend when adding new cities). */
    public const SUPPORTED_CITIES = [
        self::CITY_BARMER => 'Barmer',
    ];

    public static function cityLabel(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $catalog = self::cityCatalog();

        return $catalog[$slug] ?? Str::title(str_replace('-', ' ', $slug));
    }

    /** @return array<string, string> slug => label */
    public static function cityCatalog(): array
    {
        if (! Schema::hasTable('tv_cities')) {
            return self::SUPPORTED_CITIES;
        }

        $rows = TvCity::query()->orderBy('sort_order')->orderBy('label')->get();
        if ($rows->isEmpty()) {
            return self::SUPPORTED_CITIES;
        }

        return $rows->pluck('label', 'slug')->all();
    }

    /** @return list<string> */
    public static function registeredCitySlugs(): array
    {
        return array_keys(self::cityCatalog());
    }

    /** @return list<string> */
    public static function enabledCitySlugs(): array
    {
        if (! Schema::hasTable('tv_cities')) {
            return [self::CITY_BARMER];
        }

        $slugs = TvCity::query()
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->pluck('slug')
            ->all();

        return $slugs ?: [self::CITY_BARMER];
    }

    public static function availableCities(): array
    {
        if (! Schema::hasTable('tv_cities')) {
            return [
                [
                    'slug' => self::CITY_BARMER,
                    'label' => self::SUPPORTED_CITIES[self::CITY_BARMER],
                ],
            ];
        }

        return TvCity::query()
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->map(fn (TvCity $city) => [
                'slug' => $city->slug,
                'label' => $city->label,
            ])
            ->all();
    }

    public static function normalizeCitySlug(?string $citySlug): string
    {
        $slug = strtolower(trim((string) $citySlug));
        $enabled = self::enabledCitySlugs();

        if ($slug !== '' && in_array($slug, $enabled, true)) {
            return $slug;
        }

        return $enabled[0] ?? self::CITY_BARMER;
    }

    public static function cityHasPackages(string $citySlug, ?string $type = null): bool
    {
        $citySlug = self::normalizeCitySlug($citySlug);

        $query = TvPackage::query()
            ->where('is_active', true)
            ->where('city_slug', $citySlug);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->exists();
    }

    public static function defaultChecksForType(string $type): array
    {
        if ($type === 'owner') {
            return [
                ['check_key' => 'id_verification', 'label' => 'ID verification'],
                ['check_key' => 'ownership_docs', 'label' => 'Ownership document review'],
                ['check_key' => 'address_match', 'label' => 'Property address match'],
                ['check_key' => 'criminal_check', 'label' => 'Criminal record check'],
                ['check_key' => 'reference_check', 'label' => 'Reference check'],
            ];
        }

        return [
            ['check_key' => 'id_verification', 'label' => 'ID verification'],
            ['check_key' => 'address_validation', 'label' => 'Address validation'],
            ['check_key' => 'criminal_check', 'label' => 'Criminal record check'],
            ['check_key' => 'civil_check', 'label' => 'Civil litigation check'],
            ['check_key' => 'reference_check', 'label' => 'Previous landlord reference'],
        ];
    }

    public static function defaultFeatureTemplate(string $type): array
    {
        return array_map(fn (array $check) => [
            'key' => $check['check_key'],
            'label' => $check['label'],
            'included' => false,
        ], self::defaultChecksForType($type));
    }

    /**
     * Copy report PDFs from public disk to local (private) disk.
     *
     * @return array{scanned:int,migrated:int,skipped:int,missing:int,errors:array<int,string>}
     */
    public static function migrateLegacyReportFiles(bool $dryRun = true, bool $deletePublic = false): array
    {
        $stats = [
            'scanned' => 0,
            'migrated' => 0,
            'skipped' => 0,
            'missing' => 0,
            'errors' => [],
        ];

        TvReport::query()
            ->whereNotNull('file_path')
            ->where('file_path', '!=', '')
            ->orderBy('id')
            ->chunkById(50, function ($reports) use (&$stats, $dryRun, $deletePublic) {
                foreach ($reports as $report) {
                    $stats['scanned']++;
                    $path = $report->file_path;

                    try {
                        if (Storage::disk(self::REPORT_DISK)->exists($path)) {
                            $stats['skipped']++;

                            continue;
                        }

                        if (! Storage::disk('public')->exists($path)) {
                            $stats['missing']++;

                            continue;
                        }

                        if (! $dryRun) {
                            Storage::disk(self::REPORT_DISK)->put(
                                $path,
                                Storage::disk('public')->get($path)
                            );

                            if ($deletePublic) {
                                Storage::disk('public')->delete($path);
                            }
                        }

                        $stats['migrated']++;
                    } catch (\Throwable $e) {
                        $stats['errors'][$report->id] = $e->getMessage();
                    }
                }
            });

        return $stats;
    }

    /**
     * @return array{total:int,pending_payment:int,in_progress:int,submitted:int,overdue:int,completed:int,completed_week:int}
     */
    public static function opsStats(?string $citySlug = null): array
    {
        $base = TvOrder::query();
        if ($citySlug) {
            $base->where('city_slug', $citySlug);
        } else {
            $base->whereIn('city_slug', self::registeredCitySlugs());
        }

        return [
            'total' => (clone $base)->count(),
            'pending_payment' => (clone $base)->where('payment_status', 'pending')
                ->whereNotIn('status', ['cancelled', 'completed'])->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'submitted' => (clone $base)->where('status', 'submitted')->count(),
            'overdue' => self::overdueOrdersQuery($citySlug)->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'completed_week' => (clone $base)->where('status', 'completed')
                ->where('completed_at', '>=', now()->subDays(7))->count(),
        ];
    }

    public static function overdueOrdersQuery(?string $citySlug = null): Builder
    {
        $query = TvOrder::query()
            ->join('tv_packages', 'tv_packages.id', '=', 'tv_orders.package_id')
            ->whereIn('tv_orders.status', ['submitted', 'in_progress'])
            ->whereRaw('DATE_ADD(tv_orders.created_at, INTERVAL tv_packages.delivery_hours HOUR) < ?', [now()]);

        if ($citySlug) {
            $query->where('tv_orders.city_slug', $citySlug);
        } else {
            $query->whereIn('tv_orders.city_slug', self::registeredCitySlugs());
        }

        return $query->select('tv_orders.*');
    }

    public static function logPackagePriceChange(TvPackage $package, int $oldPrice, int $newPrice, ?int $adminId = null): void
    {
        if ($oldPrice === $newPrice) {
            return;
        }

        TvPackagePriceLog::create([
            'package_id' => $package->id,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'changed_by' => $adminId,
        ]);
    }

    public static function packageIncludesCheck(TvPackage $package, string $checkKey): bool
    {
        $features = collect($package->features ?? []);

        return $features->contains(fn ($f) => ($f['key'] ?? '') === $checkKey && ($f['included'] ?? false));
    }

    public static function generateOrderNumber(?string $citySlug = null): string
    {
        return TrustVerificationOrderNumberService::generate($citySlug);
    }

    public static function createOrder(Customer $customer, TvPackage $package, array $payload, ?Request $request = null): TvOrder
    {
        $subjectPayload = $payload['subject'] ?? [];
        if (empty($subjectPayload['consent_given'])) {
            throw new \InvalidArgumentException('Consent is required to submit a verification request.');
        }

        return DB::transaction(function () use ($customer, $package, $payload, $request, $subjectPayload) {
            $orderType = $package->type;
            $citySlug = self::normalizeCitySlug($payload['city_slug'] ?? null);

            $order = TvOrder::create(array_merge([
                'order_number' => self::generateOrderNumber($citySlug),
                'customer_id' => $customer->id,
                'package_id' => $package->id,
                'order_type' => $orderType,
                'city_slug' => $citySlug,
                'requester_name' => $payload['requester_name'] ?? trim(($customer->name ?? '').' '.($customer->last_name ?? '')),
                'requester_email' => $payload['requester_email'] ?? $customer->email,
                'requester_phone' => $payload['requester_phone'] ?? $customer->mobile,
                'status' => 'submitted',
                'payment_status' => 'pending',
                'amount' => $package->price,
                'requester_notes' => $payload['notes'] ?? null,
            ], TrustVerificationConsentService::orderConsentAttributes($request)));

            TvSubject::create([
                'order_id' => $order->id,
                'subject_type' => $orderType,
                'full_name' => $subjectPayload['full_name'] ?? '',
                'phone' => $subjectPayload['phone'] ?? '',
                'email' => $subjectPayload['email'] ?? null,
                'current_address' => $subjectPayload['current_address'] ?? null,
                'permanent_address' => $subjectPayload['permanent_address'] ?? null,
                'property_address' => $subjectPayload['property_address'] ?? null,
                'id_type' => $subjectPayload['id_type'] ?? null,
                'id_number_hint' => isset($subjectPayload['id_number'])
                    ? self::maskIdNumber($subjectPayload['id_number'])
                    : null,
                'employment_company' => $subjectPayload['employment_company'] ?? null,
                'employment_role' => $subjectPayload['employment_role'] ?? null,
                'consent_given' => (bool) ($subjectPayload['consent_given'] ?? false),
                'consent_at' => ! empty($subjectPayload['consent_given']) ? now() : null,
            ]);

            foreach (self::defaultChecksForType($orderType) as $check) {
                $included = self::packageIncludesCheck($package, $check['check_key']);
                TvCheckItem::create([
                    'order_id' => $order->id,
                    'check_key' => $check['check_key'],
                    'label' => $check['label'],
                    'status' => $included ? 'pending' : 'na',
                ]);
            }

            TvReport::create(['order_id' => $order->id]);

            return $order->fresh(['package', 'subject', 'checkItems', 'report']);
        });
    }

    public static function maskIdNumber(string $value): string
    {
        $value = trim($value);
        if (strlen($value) <= 4) {
            return str_repeat('*', max(0, strlen($value) - 1)).substr($value, -1);
        }

        return str_repeat('*', strlen($value) - 4).substr($value, -4);
    }

    /**
     * @param  array<string, ?string>|null  $timestamps  Pre-resolved map from TrustVerificationOrderTimestampsService
     */
    public static function formatOrder(TvOrder $order, bool $includeReportFile = false, ?array $timestamps = null): array
    {
        $order->loadMissing(['package', 'subject', 'checkItems', 'report', 'documents', 'referenceContacts', 'policeVerification']);

        $fileExists = $order->report ? self::reportFileExists($order->report) : false;
        $paid = in_array($order->payment_status, ['paid', 'waived'], true);
        $canDownloadReport = $includeReportFile && $fileExists;

        $report = null;
        if ($order->report) {
            $report = [
                'risk_level' => $order->report->risk_level,
                'summary' => $order->report->summary,
                'uploaded' => $fileExists,
                'has_report_file' => $fileExists,
                'download_available' => $canDownloadReport,
                'awaiting_upload' => $order->status === 'completed'
                    && $paid
                    && ! $fileExists,
                'deleted_at' => optional($order->report->deleted_at)?->toIso8601String(),
            ];
        }

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'order_type' => $order->order_type,
            'city_slug' => $order->city_slug,
            'city_label' => self::cityLabel($order->city_slug),
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'automation_status' => $order->automation_status,
            'amount' => $order->amount,
            'currency' => $order->package?->currency ?? 'INR',
            'requester_name' => $order->requester_name,
            'requester_email' => $order->requester_email,
            'requester_phone' => $order->requester_phone,
            'requester_notes' => $order->requester_notes,
            'admin_notes' => $order->admin_notes,
            'completed_at' => optional($order->completed_at)?->toIso8601String(),
            'created_at' => optional($order->created_at)?->toIso8601String(),
            'updated_at' => optional($order->updated_at)?->toIso8601String(),
            'timestamps' => $timestamps ?? TrustVerificationOrderTimestampsService::resolve($order),
            'package' => $order->package ? [
                'id' => $order->package->id,
                'name' => $order->package->name,
                'slug' => $order->package->slug,
                'delivery_hours' => $order->package->delivery_hours,
            ] : null,
            'subject' => $order->subject ? [
                'full_name' => $order->subject->full_name,
                'phone' => $order->subject->phone,
                'email' => $order->subject->email,
                'current_address' => $order->subject->current_address,
                'permanent_address' => $order->subject->permanent_address,
                'property_address' => $order->subject->property_address,
                'id_type' => $order->subject->id_type,
                'id_number_hint' => $order->subject->id_number_hint,
                'employment_company' => $order->subject->employment_company,
                'employment_role' => $order->subject->employment_role,
                'consent_given' => $order->subject->consent_given,
            ] : null,
            'check_items' => $order->checkItems->map(fn (TvCheckItem $item) => [
                'check_key' => $item->check_key,
                'label' => $item->label,
                'status' => $item->status,
                'notes' => $item->notes,
                'completed_at' => optional($item->completed_at)?->toIso8601String(),
            ])->values()->all(),
            'report' => $report,
            'can_download_report' => $canDownloadReport,
            'documents' => $order->documents->map(
                fn ($doc) => TrustVerificationDocumentService::formatDocument($doc)
            )->values()->all(),
            'documents_complete' => TrustVerificationDocumentService::missingRequiredTypes($order) === [],
            'can_cancel' => self::canCustomerCancel($order),
            'reference_check_enabled' => TrustVerificationReferenceService::orderHasReferenceCheck($order),
            'references' => TrustVerificationReferenceService::formatListForCustomer($order),
            'reference_progress' => TrustVerificationReferenceService::progressForOrder($order),
            'police_verification_enabled' => TrustVerificationPoliceVerificationService::orderRequiresPoliceVerification($order),
            'police_verification' => TrustVerificationPoliceVerificationService::summaryForCustomer($order),
            'police_verification_report' => TrustVerificationPoliceVerificationService::formatForReport($order),
            'verification_badge' => TrustVerificationIssuedBadgeService::formatForOrder($order),
        ];
    }

    public static function formatPackage(TvPackage $package): array
    {
        return [
            'id' => $package->id,
            'type' => $package->type,
            'city_slug' => $package->city_slug,
            'name' => $package->name,
            'slug' => $package->slug,
            'price' => $package->price,
            'currency' => $package->currency,
            'delivery_hours' => $package->delivery_hours,
            'features' => $package->features ?? [],
        ];
    }

    public static function reportDiskForPath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Storage::disk(self::REPORT_DISK)->exists($path)) {
            return self::REPORT_DISK;
        }

        if (Storage::disk('public')->exists($path)) {
            return 'public';
        }

        return null;
    }

    public static function reportFileExists(?TvReport $report): bool
    {
        return self::reportFileIsReadable($report);
    }

    public static function reportFileIsReadable(?TvReport $report): bool
    {
        if (! TrustVerificationPiiRetentionService::reportFileAvailable($report)) {
            return false;
        }

        $disk = self::reportDiskForPath($report->file_path);
        if (! $disk) {
            return false;
        }

        try {
            $size = Storage::disk($disk)->size($report->file_path);
            if ($size < self::MIN_REPORT_PDF_BYTES) {
                return false;
            }

            $stream = Storage::disk($disk)->readStream($report->file_path);
            if (! $stream) {
                return false;
            }
            $head = fread($stream, 8);
            fclose($stream);

            return is_string($head) && str_starts_with($head, '%PDF');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @throws \RuntimeException
     */
    public static function assertValidReportUpload(UploadedFile $file): void
    {
        $path = $file->getRealPath();
        if (! $path || ! is_readable($path)) {
            throw new \RuntimeException('Could not read the uploaded PDF.');
        }

        $size = $file->getSize() ?: filesize($path);
        if ($size < self::MIN_REPORT_PDF_BYTES) {
            throw new \RuntimeException(
                'The PDF is too small or empty. Upload a complete verification report (at least 1 KB).'
            );
        }

        $head = file_get_contents($path, false, null, 0, 8);
        if (! is_string($head) || ! str_starts_with($head, '%PDF')) {
            throw new \RuntimeException('The file is not a valid PDF document.');
        }
    }

    public static function downloadReportResponse(TvOrder $order): StreamedResponse
    {
        $order->loadMissing('report');

        if (! self::reportFileIsReadable($order->report)) {
            abort(404, 'Report is not available yet. Please contact support.');
        }

        $disk = self::reportDiskForPath($order->report->file_path);

        if (! $disk) {
            abort(404, 'Report not found or has been removed');
        }

        $filename = $order->order_number.'-verification-report.pdf';

        return Storage::disk($disk)->download($order->report->file_path, $filename);
    }

    public static function signedEmailReportUrl(TvOrder $order): ?string
    {
        if (! self::reportFileExists($order->report)) {
            return null;
        }

        return URL::temporarySignedRoute(
            'trust-verification.reports.email-download',
            now()->addHours(72),
            ['order' => $order->id]
        );
    }

    public static function storeReportFile(TvOrder $order, UploadedFile $file, ?int $adminUserId, ?string $riskLevel, ?string $summary): TvReport
    {
        self::assertValidReportUpload($file);

        $path = $file->store('trust-verification/reports/'.$order->id, self::REPORT_DISK);

        $report = $order->report ?: new TvReport(['order_id' => $order->id]);
        if ($report->file_path && ! $report->deleted_at) {
            $oldDisk = self::reportDiskForPath($report->file_path);
            if ($oldDisk) {
                Storage::disk($oldDisk)->delete($report->file_path);
            }
        }
        $report->file_path = $path;
        $report->risk_level = $riskLevel;
        $report->summary = $summary;
        $report->uploaded_by = $adminUserId;
        $report->deleted_at = null;
        $report->deleted_by_type = null;
        $report->deleted_by_id = null;
        $report->delete_reason = null;
        $report->save();

        if ($order->status !== 'completed') {
            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        TrustVerificationTrustBadgeService::onOrderEvent($order->fresh(), 'verification_completed');

        return $report->fresh();
    }

    public static function canCustomerCancel(TvOrder $order): bool
    {
        return $order->status === 'submitted'
            && in_array($order->payment_status, ['pending', 'failed'], true);
    }

    public static function cancelOrder(TvOrder $order, Customer $customer): TvOrder
    {
        if ((int) $order->customer_id !== (int) $customer->id) {
            throw new \RuntimeException('Unauthorized');
        }

        if ($order->status === 'cancelled') {
            return $order->fresh(['package', 'subject', 'checkItems', 'report']);
        }

        if (! self::canCustomerCancel($order)) {
            throw new \RuntimeException('This order cannot be cancelled.');
        }

        $order->update(['status' => 'cancelled']);

        return $order->fresh(['package', 'subject', 'checkItems', 'report']);
    }
}

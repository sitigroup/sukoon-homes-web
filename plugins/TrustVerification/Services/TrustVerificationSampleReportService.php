<?php

namespace App\Plugins\TrustVerification\Services;

use App\Plugins\TrustVerification\Models\TvSampleReport;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrustVerificationSampleReportService
{
    public const DISK = TrustVerificationService::REPORT_DISK;

    public const MAX_BYTES = 10 * 1024 * 1024;

    /** @return list<string> */
    public static function defaultChecks(): array
    {
        return [
            'ID Verification',
            'Address Validation',
            'Criminal Record Check',
            'Civil Litigation Check',
            'Reference Check',
            'Risk Summary',
        ];
    }

    /** @return list<string> */
    public static function reportTypes(): array
    {
        return [TvSampleReport::TYPE_TENANT, TvSampleReport::TYPE_OWNER];
    }

    public static function assertValidSamplePdf(UploadedFile $file): void
    {
        $path = $file->getRealPath();
        if (! $path || ! is_readable($path)) {
            throw new \RuntimeException('Could not read the uploaded PDF.');
        }

        $size = $file->getSize() ?: filesize($path);
        if ($size > self::MAX_BYTES) {
            throw new \RuntimeException('Sample PDF must be 10 MB or smaller.');
        }

        TrustVerificationService::assertValidReportUpload($file);

        $head = file_get_contents($path, false, null, 0, 131072);
        if (! is_string($head)) {
            throw new \RuntimeException('Could not validate PDF contents.');
        }

        if (stripos($head, '/Encrypt') !== false) {
            throw new \RuntimeException('Password-protected PDFs are not allowed for sample reports.');
        }

        if (preg_match('/\/JavaScript|\/JS\s*\(/i', $head)) {
            throw new \RuntimeException('PDFs with embedded scripts are not allowed.');
        }
    }

    public static function storePdf(TvSampleReport $sample, UploadedFile $file): TvSampleReport
    {
        self::assertValidSamplePdf($file);

        if ($sample->file_path) {
            self::deleteStoredFile($sample->file_path);
        }

        $path = $file->store('trust-verification/sample-reports/'.$sample->id, self::DISK);

        $sample->file_path = $path;
        $sample->original_filename = TrustVerificationDocumentUploadValidator::sanitizeDisplayFilename(
            $file->getClientOriginalName()
        );
        $sample->file_size_bytes = (int) ($file->getSize() ?: filesize($file->getRealPath() ?: ''));
        $sample->save();

        return $sample->fresh();
    }

    public static function deleteStoredFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public static function fileIsReadable(TvSampleReport $sample): bool
    {
        if (! $sample->file_path || ! $sample->is_active) {
            return false;
        }

        if (! Storage::disk(self::DISK)->exists($sample->file_path)) {
            return false;
        }

        try {
            $head = Storage::disk(self::DISK)->read($sample->file_path, 5);

            return is_string($head) && str_starts_with($head, '%PDF');
        } catch (\Throwable) {
            return false;
        }
    }

    public static function resolveForPublic(string $type, ?string $citySlug = null, ?int $packageId = null): ?TvSampleReport
    {
        if (! in_array($type, self::reportTypes(), true)) {
            return null;
        }

        $candidates = TvSampleReport::query()
            ->active()
            ->ofType($type)
            ->whereNotNull('file_path')
            ->with('package')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (TvSampleReport $row) => self::fileIsReadable($row));

        $best = null;
        $bestScore = -1;

        foreach ($candidates as $row) {
            if ($row->city_slug && $row->city_slug !== $citySlug) {
                continue;
            }
            if ($row->package_id && (int) $row->package_id !== (int) $packageId) {
                continue;
            }

            $score = 0;
            if ($row->package_id && $packageId) {
                $score += 4;
            }
            if ($row->city_slug && $citySlug) {
                $score += 2;
            }
            if (! $row->package_id && ! $row->city_slug) {
                $score += 1;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $row;
            }
        }

        return $best;
    }

    /** @return array<string, mixed>|null */
    public static function publicPayload(string $type, ?string $citySlug = null, ?int $packageId = null): ?array
    {
        $sample = self::resolveForPublic($type, $citySlug, $packageId);
        if (! $sample) {
            return null;
        }

        return self::serializePublic($sample);
    }

    /** @return array<string, mixed> */
    public static function serializePublic(TvSampleReport $sample): array
    {
        return [
            'id' => $sample->id,
            'report_type' => $sample->report_type,
            'title' => $sample->title,
            'description' => $sample->description,
            'city_slug' => $sample->city_slug,
            'package_id' => $sample->package_id,
            'checks' => self::defaultChecks(),
            'has_file' => true,
        ];
    }

    public static function downloadResponse(TvSampleReport $sample, bool $inline = false): StreamedResponse
    {
        if (! self::fileIsReadable($sample)) {
            abort(404, 'Sample report not available.');
        }

        $filename = $sample->original_filename ?: 'sukoon-sample-'.$sample->report_type.'-report.pdf';
        if (! str_ends_with(strtolower($filename), '.pdf')) {
            $filename .= '.pdf';
        }

        $disposition = $inline ? 'inline' : 'attachment';

        return Storage::disk(self::DISK)->response(
            $sample->file_path,
            $filename,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => $disposition.'; filename="'.addslashes($filename).'"',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    public static function logViewed(Request $request, TvSampleReport $sample, ?array $extra = null): void
    {
        TrustVerificationAuditLogService::log([
            'order_id' => null,
            'customer_id' => $request->user()?->id,
            'action' => TrustVerificationAuditLogService::ACTION_SAMPLE_REPORT_VIEWED,
            'description' => 'Viewed sample report preview',
            'metadata' => array_merge([
                'sample_report_id' => $sample->id,
                'report_type' => $sample->report_type,
                'city_slug' => $sample->city_slug,
                'package_id' => $sample->package_id,
            ], $extra ?? []),
        ], $request);
    }

    public static function logDownloaded(Request $request, TvSampleReport $sample, ?array $extra = null): void
    {
        TrustVerificationAuditLogService::log([
            'order_id' => null,
            'customer_id' => $request->user()?->id,
            'action' => TrustVerificationAuditLogService::ACTION_SAMPLE_REPORT_DOWNLOADED,
            'description' => 'Downloaded sample report PDF',
            'metadata' => array_merge([
                'sample_report_id' => $sample->id,
                'report_type' => $sample->report_type,
                'city_slug' => $sample->city_slug,
                'package_id' => $sample->package_id,
            ], $extra ?? []),
        ], $request);
    }
}

<?php

namespace App\Plugins\TrustVerification\Services;

use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvOrderDocument;
use App\Plugins\TrustVerification\Models\TvReport;
use Illuminate\Support\Facades\Storage;

class TrustVerificationPiiRetentionService
{
    public const DELETED_BY_SYSTEM = 'system';

    public const DELETED_BY_ADMIN = 'admin';

    public const DELETED_BY_CUSTOMER = 'customer';

    /** @return array{document_retention_days: int, report_retention_days: int, delete_cancelled_unpaid_after_days: int} */
    public static function retentionSettings(): array
    {
        return [
            'document_retention_days' => max(1, (int) TrustVerificationSettingsService::get('document_retention_days', 90)),
            'report_retention_days' => max(1, (int) TrustVerificationSettingsService::get('report_retention_days', 365)),
            'delete_cancelled_unpaid_after_days' => max(1, (int) TrustVerificationSettingsService::get('delete_cancelled_unpaid_after_days', 7)),
        ];
    }

    public static function documentFileAvailable(?TvOrderDocument $document): bool
    {
        if (! $document || $document->deleted_at || ! $document->file_path) {
            return false;
        }

        return Storage::disk(TrustVerificationDocumentService::DISK)->exists($document->file_path);
    }

    public static function reportFileAvailable(?TvReport $report): bool
    {
        if (! $report || $report->deleted_at || ! $report->file_path) {
            return false;
        }

        return (bool) TrustVerificationService::reportDiskForPath($report->file_path);
    }

    /**
     * @return array{dry_run: bool, cancelled_orders: int, documents_deleted: int, reports_deleted: int, paths: list<string>}
     */
    public static function runCleanup(bool $dryRun = false): array
    {
        $settings = self::retentionSettings();
        $stats = [
            'dry_run' => $dryRun,
            'cancelled_orders' => 0,
            'documents_deleted' => 0,
            'reports_deleted' => 0,
            'paths' => [],
        ];

        $cancelledCutoff = now()->subDays($settings['delete_cancelled_unpaid_after_days']);
        TvOrder::query()
            ->where('status', 'cancelled')
            ->whereIn('payment_status', ['pending', 'failed'])
            ->where('updated_at', '<=', $cancelledCutoff)
            ->orderBy('id')
            ->chunkById(50, function ($orders) use (&$stats, $dryRun) {
                foreach ($orders as $order) {
                    $stats['cancelled_orders']++;
                    $result = self::purgeAllPiiForOrder(
                        $order,
                        self::DELETED_BY_SYSTEM,
                        null,
                        'Cancelled unpaid order retention policy',
                        $dryRun
                    );
                    $stats['documents_deleted'] += $result['documents'];
                    $stats['reports_deleted'] += $result['reports'];
                    $stats['paths'] = array_merge($stats['paths'], $result['paths']);
                    self::auditSystemPurge($order, $result, $dryRun);
                }
            });

        $documentCutoff = now()->subDays($settings['document_retention_days']);
        TvOrder::query()
            ->where('status', 'completed')
            ->where(function ($q) use ($documentCutoff) {
                $q->where('completed_at', '<=', $documentCutoff)
                    ->orWhere(function ($q2) use ($documentCutoff) {
                        $q2->whereNull('completed_at')->where('updated_at', '<=', $documentCutoff);
                    });
            })
            ->orderBy('id')
            ->chunkById(50, function ($orders) use (&$stats, $dryRun) {
                foreach ($orders as $order) {
                    $result = self::purgeOrderDocuments(
                        $order,
                        self::DELETED_BY_SYSTEM,
                        null,
                        'Document retention policy',
                        $dryRun
                    );
                    $stats['documents_deleted'] += $result['count'];
                    $stats['paths'] = array_merge($stats['paths'], $result['paths']);
                    if ($result['count'] > 0) {
                        self::auditSystemPurge($order, [
                            'documents' => $result['count'],
                            'reports' => 0,
                            'paths' => $result['paths'],
                        ], $dryRun);
                    }
                }
            });

        $reportCutoff = now()->subDays($settings['report_retention_days']);
        TvOrder::query()
            ->where('status', 'completed')
            ->where(function ($q) use ($reportCutoff) {
                $q->where('completed_at', '<=', $reportCutoff)
                    ->orWhere(function ($q2) use ($reportCutoff) {
                        $q2->whereNull('completed_at')->where('updated_at', '<=', $reportCutoff);
                    });
            })
            ->with('report')
            ->orderBy('id')
            ->chunkById(50, function ($orders) use (&$stats, $dryRun) {
                foreach ($orders as $order) {
                    if (! $order->report) {
                        continue;
                    }
                    if (self::purgeReport(
                        $order->report,
                        self::DELETED_BY_SYSTEM,
                        null,
                        'Report retention policy',
                        $dryRun
                    )) {
                        $stats['reports_deleted']++;
                        $reportPath = $order->report->file_path;
                        if ($reportPath) {
                            $stats['paths'][] = $reportPath;
                        }
                        self::auditSystemPurge($order, [
                            'documents' => 0,
                            'reports' => 1,
                            'paths' => $reportPath ? [$reportPath] : [],
                        ], $dryRun);
                    }
                }
            });

        $stats['paths'] = array_values(array_unique($stats['paths']));

        return $stats;
    }

    /**
     * @return array{documents: int, reports: int, paths: list<string>}
     */
    public static function purgeAllPiiForOrder(
        TvOrder $order,
        string $deletedByType,
        ?int $deletedById,
        string $reason,
        bool $dryRun = false
    ): array {
        $docResult = self::purgeOrderDocuments($order, $deletedByType, $deletedById, $reason, $dryRun);
        $reports = 0;
        $paths = $docResult['paths'];

        $order->loadMissing('report');
        if ($order->report && self::purgeReport($order->report, $deletedByType, $deletedById, $reason, $dryRun)) {
            $reports = 1;
            if ($order->report->file_path) {
                $paths[] = $order->report->file_path;
            }
        }

        return [
            'documents' => $docResult['count'],
            'reports' => $reports,
            'paths' => $paths,
        ];
    }

    /**
     * @return array{count: int, paths: list<string>}
     */
    public static function purgeOrderDocuments(
        TvOrder $order,
        string $deletedByType,
        ?int $deletedById,
        string $reason,
        bool $dryRun = false
    ): array {
        $count = 0;
        $paths = [];

        $order->loadMissing('documents');
        foreach ($order->documents as $document) {
            if (self::purgeDocument($document, $deletedByType, $deletedById, $reason, $dryRun)) {
                $count++;
                if ($document->file_path) {
                    $paths[] = $document->file_path;
                }
            }
        }

        return ['count' => $count, 'paths' => $paths];
    }

    public static function purgeDocument(
        TvOrderDocument $document,
        string $deletedByType,
        ?int $deletedById,
        string $reason,
        bool $dryRun = false
    ): bool {
        if ($document->deleted_at) {
            return false;
        }

        $path = $document->file_path;

        if ($dryRun) {
            return $path !== null && $path !== '';
        }

        if (! $path) {
            $document->update([
                'deleted_at' => now(),
                'deleted_by_type' => $deletedByType,
                'deleted_by_id' => $deletedById,
                'delete_reason' => mb_substr($reason, 0, 255),
            ]);

            return true;
        }

        if ($path) {
            Storage::disk(TrustVerificationDocumentService::DISK)->delete($path);
        }

        $document->update([
            'deleted_at' => now(),
            'deleted_by_type' => $deletedByType,
            'deleted_by_id' => $deletedById,
            'delete_reason' => mb_substr($reason, 0, 255),
        ]);

        return true;
    }

    public static function purgeReport(
        TvReport $report,
        string $deletedByType,
        ?int $deletedById,
        string $reason,
        bool $dryRun = false
    ): bool {
        if ($report->deleted_at) {
            return false;
        }

        if (! $report->file_path) {
            if ($dryRun) {
                return false;
            }

            $report->update([
                'deleted_at' => now(),
                'deleted_by_type' => $deletedByType,
                'deleted_by_id' => $deletedById,
                'delete_reason' => mb_substr($reason, 0, 255),
            ]);

            return false;
        }

        $path = $report->file_path;
        $disk = TrustVerificationService::reportDiskForPath($path);

        if ($dryRun) {
            return (bool) $disk;
        }

        if ($disk) {
            Storage::disk($disk)->delete($path);
        }

        $report->update([
            'deleted_at' => now(),
            'deleted_by_type' => $deletedByType,
            'deleted_by_id' => $deletedById,
            'delete_reason' => mb_substr($reason, 0, 255),
        ]);

        return true;
    }

    /**
     * @param  array{documents: int, reports: int, paths: list<string>}  $result
     */
    private static function auditSystemPurge(TvOrder $order, array $result, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }

        if ($result['documents'] > 0) {
            TrustVerificationAuditLogService::log([
                'order_id' => $order->id,
                'action' => TrustVerificationAuditLogService::ACTION_SYSTEM_DELETED_DOCUMENTS,
                'description' => 'System deleted '.$result['documents'].' document file(s) per retention policy',
                'metadata' => ['count' => $result['documents'], 'paths' => $result['paths']],
            ]);
        }

        if ($result['reports'] > 0) {
            TrustVerificationAuditLogService::log([
                'order_id' => $order->id,
                'action' => TrustVerificationAuditLogService::ACTION_SYSTEM_DELETED_REPORT,
                'description' => 'System deleted verification report file per retention policy',
                'metadata' => ['paths' => $result['paths']],
            ]);
        }
    }
}

<?php

namespace App\Plugins\TrustVerification\Services;

use App\Plugins\TrustVerification\Models\TvAuditLog;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvOrderDocument;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TrustVerificationOrderTimestampsService
{
    public const TZ = 'Asia/Kolkata';

    /** @var list<string> */
    private const AUDIT_ACTIONS = [
        TrustVerificationAuditLogService::ACTION_PAYMENT_WEBHOOK_MARKED_PAID,
        TrustVerificationAuditLogService::ACTION_ADMIN_CHANGED_PAYMENT_STATUS,
        TrustVerificationAuditLogService::ACTION_ADMIN_CHANGED_ORDER_STATUS,
        TrustVerificationAuditLogService::ACTION_ADMIN_UPLOADED_REPORT,
        TrustVerificationAuditLogService::ACTION_CUSTOMER_UPLOADED_DOCUMENT,
        TrustVerificationAuditLogService::ACTION_CUSTOMER_UPLOAD_ACCEPTED,
    ];

    /**
     * @param  Collection<int, TvOrder>  $orders
     * @return array<int, array<string, ?string>>
     */
    public static function resolveForOrders(Collection $orders): array
    {
        if ($orders->isEmpty()) {
            return [];
        }

        $orders->loadMissing(['package', 'report', 'documents', 'automationRuns', 'referenceContacts', 'policeVerification', 'checkItems']);

        $auditByOrder = TvAuditLog::query()
            ->whereIn('order_id', $orders->pluck('id'))
            ->whereIn('action', self::AUDIT_ACTIONS)
            ->orderBy('created_at')
            ->get()
            ->groupBy('order_id');

        $map = [];
        foreach ($orders as $order) {
            $logs = $auditByOrder->get($order->id, collect());
            $map[$order->id] = self::resolve($order, $logs);
        }

        return $map;
    }

    /**
     * @param  Collection<int, TvAuditLog>|null  $auditLogs
     * @return array<string, ?string>
     */
    public static function resolve(TvOrder $order, ?Collection $auditLogs = null): array
    {
        $order->loadMissing(['package', 'report', 'documents', 'automationRuns']);

        if ($auditLogs === null) {
            $auditLogs = TvAuditLog::query()
                ->where('order_id', $order->id)
                ->whereIn('action', self::AUDIT_ACTIONS)
                ->orderBy('created_at')
                ->get();
        }

        $paidAt = self::resolvePaidAt($order, $auditLogs);
        $verificationStartedAt = self::resolveVerificationStartedAt($order, $auditLogs);
        $reportUploadedAt = self::resolveReportUploadedAt($order, $auditLogs);

        $activeDocuments = $order->documents->filter(
            fn (TvOrderDocument $doc) => ! $doc->deleted_at
        );
        $documentUploadedAt = $activeDocuments->max('created_at');

        $runs = $order->automationRuns;
        $automationStartedAt = $runs->min('started_at') ?? $runs->min('created_at');
        $automationCompletedAt = $runs
            ->filter(fn ($run) => $run->completed_at !== null)
            ->max('completed_at');

        $slaDueAt = $order->package && $order->created_at
            ? $order->created_at->copy()->addHours((int) $order->package->delivery_hours)
            : null;

        $referencesSubmittedAt = TrustVerificationReferenceService::referencesSubmittedAt($order);
        $referencesCompletedAt = TrustVerificationReferenceService::referencesCompletedAt($order);
        $policeSubmittedAt = TrustVerificationPoliceVerificationService::submittedAt($order);
        $policeCompletedAt = TrustVerificationPoliceVerificationService::completedAt($order);

        return [
            'created_at' => self::toIso($order->created_at),
            'updated_at' => self::toIso($order->updated_at),
            'consent_given_at' => self::toIso($order->consent_given_at),
            'paid_at' => self::toIso($paidAt),
            'document_uploaded_at' => self::toIso($documentUploadedAt),
            'verification_started_at' => self::toIso($verificationStartedAt),
            'references_submitted_at' => self::toIso($referencesSubmittedAt),
            'references_completed_at' => self::toIso($referencesCompletedAt),
            'police_verification_submitted_at' => self::toIso($policeSubmittedAt),
            'police_verification_completed_at' => self::toIso($policeCompletedAt),
            'review_completed_at' => self::toIso($order->completed_at),
            'report_created_at' => self::toIso($order->report?->created_at),
            'report_uploaded_at' => self::toIso($reportUploadedAt),
            'automation_started_at' => self::toIso($automationStartedAt),
            'automation_completed_at' => self::toIso($automationCompletedAt),
            'sla_due_at' => self::toIso($slaDueAt),
        ];
    }

    public static function formatDisplay(?Carbon $dt): string
    {
        if (! $dt) {
            return 'Not recorded';
        }

        return $dt->copy()->timezone(self::TZ)->format('j M Y, g:i A');
    }

    public static function slaDueAt(TvOrder $order): ?Carbon
    {
        $order->loadMissing('package');
        if (! $order->package || ! $order->created_at) {
            return null;
        }

        return $order->created_at->copy()->addHours((int) $order->package->delivery_hours);
    }

    public static function isOverdue(TvOrder $order, ?Carbon $dueAt = null): bool
    {
        $dueAt ??= self::slaDueAt($order);
        if (! $dueAt) {
            return false;
        }

        return $dueAt->isPast()
            && in_array($order->status, ['submitted', 'in_progress'], true);
    }

    public static function overdueDuration(TvOrder $order, ?Carbon $dueAt = null): ?string
    {
        $dueAt ??= self::slaDueAt($order);
        if (! $dueAt || ! self::isOverdue($order, $dueAt)) {
            return null;
        }

        return $dueAt->diffForHumans(now(), true).' overdue';
    }

    private static function resolvePaidAt(TvOrder $order, Collection $auditLogs): ?Carbon
    {
        if (! in_array($order->payment_status, ['paid', 'waived'], true)) {
            return null;
        }

        $fromWebhook = self::firstAuditAt($auditLogs, TrustVerificationAuditLogService::ACTION_PAYMENT_WEBHOOK_MARKED_PAID);
        if ($fromWebhook) {
            return $fromWebhook;
        }

        $fromAdmin = self::firstAuditAt(
            $auditLogs,
            TrustVerificationAuditLogService::ACTION_ADMIN_CHANGED_PAYMENT_STATUS,
            fn (TvAuditLog $log) => in_array((string) data_get($log->metadata, 'to'), ['paid', 'waived'], true)
        );
        if ($fromAdmin) {
            return $fromAdmin;
        }

        return $order->updated_at;
    }

    private static function resolveVerificationStartedAt(TvOrder $order, Collection $auditLogs): ?Carbon
    {
        $fromStatus = self::firstAuditAt(
            $auditLogs,
            TrustVerificationAuditLogService::ACTION_ADMIN_CHANGED_ORDER_STATUS,
            fn (TvAuditLog $log) => (string) data_get($log->metadata, 'to') === 'in_progress'
        );
        if ($fromStatus) {
            return $fromStatus;
        }

        $runs = $order->automationRuns;
        $fromAutomation = $runs->min('started_at') ?? $runs->min('created_at');
        if ($fromAutomation) {
            return $fromAutomation;
        }

        if ($order->status === 'in_progress' && $order->updated_at) {
            return $order->updated_at;
        }

        return null;
    }

    private static function resolveReportUploadedAt(TvOrder $order, Collection $auditLogs): ?Carbon
    {
        if (! $order->report || ! TrustVerificationService::reportFileExists($order->report)) {
            return null;
        }

        $fromAudit = self::firstAuditAt(
            $auditLogs,
            TrustVerificationAuditLogService::ACTION_ADMIN_UPLOADED_REPORT
        );
        if ($fromAudit) {
            return $fromAudit;
        }

        $report = $order->report;
        if ($report->updated_at && $report->created_at && $report->updated_at->gt($report->created_at)) {
            return $report->updated_at;
        }

        return $report->created_at;
    }

    /**
     * @param  callable(TvAuditLog): bool|null  $filter
     */
    private static function firstAuditAt(Collection $logs, string $action, ?callable $filter = null): ?Carbon
    {
        foreach ($logs as $log) {
            if ($log->action !== $action) {
                continue;
            }
            if ($filter && ! $filter($log)) {
                continue;
            }

            return $log->created_at;
        }

        return null;
    }

    private static function toIso(mixed $dt): ?string
    {
        if ($dt === null) {
            return null;
        }

        if ($dt instanceof Carbon) {
            return $dt->toIso8601String();
        }

        return optional($dt)?->toIso8601String();
    }
}

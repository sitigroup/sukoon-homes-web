<?php

namespace App\Plugins\TrustVerification\Services;

use App\Plugins\TrustVerification\Models\TvAuditLog;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvOrderDocument;
use Illuminate\Http\Request;

class TrustVerificationAuditLogService
{
    public const ACTION_ADMIN_VIEWED_ORDER = 'admin_viewed_order';

    public const ACTION_ADMIN_DOWNLOADED_DOCUMENT = 'admin_downloaded_document';

    public const ACTION_ADMIN_CHANGED_ORDER_STATUS = 'admin_changed_order_status';

    public const ACTION_ADMIN_CHANGED_PAYMENT_STATUS = 'admin_changed_payment_status';

    public const ACTION_ADMIN_UPLOADED_REPORT = 'admin_uploaded_report';

    public const ACTION_ADMIN_RAN_AUTOMATION = 'admin_ran_automation';

    public const ACTION_ADMIN_DOWNLOADED_REPORT = 'admin_downloaded_report';

    public const ACTION_CUSTOMER_CREATED_ORDER = 'customer_created_order';

    public const ACTION_CUSTOMER_UPLOADED_DOCUMENT = 'customer_uploaded_document';

    public const ACTION_CUSTOMER_UPLOAD_ACCEPTED = 'customer_upload_accepted';

    public const ACTION_CUSTOMER_UPLOAD_REJECTED = 'customer_upload_rejected';

    public const ACTION_ADMIN_DOCUMENT_DOWNLOAD_BLOCKED = 'admin_document_download_blocked';

    public const ACTION_CUSTOMER_DOWNLOADED_REPORT = 'customer_downloaded_report';

    public const ACTION_CUSTOMER_CANCELLED_ORDER = 'customer_cancelled_order';

    public const ACTION_SYSTEM_DELETED_DOCUMENTS = 'system_deleted_documents';

    public const ACTION_SYSTEM_DELETED_REPORT = 'system_deleted_report';

    public const ACTION_ADMIN_DELETED_DOCUMENTS = 'admin_deleted_documents';

    public const ACTION_ADMIN_DELETED_REPORT = 'admin_deleted_report';

    public const ACTION_PAYMENT_WEBHOOK_RECEIVED = 'payment_webhook_received';

    public const ACTION_PAYMENT_WEBHOOK_REJECTED = 'payment_webhook_rejected';

    public const ACTION_PAYMENT_WEBHOOK_DUPLICATE = 'payment_webhook_duplicate';

    public const ACTION_PAYMENT_WEBHOOK_MARKED_PAID = 'payment_webhook_marked_paid';

    public const ACTION_PAYMENT_WEBHOOK_DOWNGRADE_BLOCKED = 'payment_webhook_downgrade_blocked';

    public const ACTION_RATE_LIMIT_EXCEEDED = 'rate_limit_exceeded';

    public const ACTION_CONTENT_BLOCK_VIEWED = 'content_block_viewed';

    public const ACTION_CONTENT_BLOCK_UPDATED = 'content_block_updated';

    public const ACTION_CONTENT_BLOCK_PUBLISHED = 'content_block_published';

    public const ACTION_CONTENT_BLOCK_UNPUBLISHED = 'content_block_unpublished';

    public const ACTION_CONTENT_BLOCK_RESET = 'content_block_reset';

    public const ACTION_SAMPLE_REPORT_VIEWED = 'sample_report_viewed';

    public const ACTION_SAMPLE_REPORT_DOWNLOADED = 'sample_report_downloaded';

    public const ACTION_ADMIN_UPLOADED_SAMPLE_REPORT = 'admin_uploaded_sample_report';

    public const ACTION_CUSTOMER_SUBMITTED_REFERENCES = 'customer_submitted_references';

    public const ACTION_ADMIN_UPDATED_REFERENCE = 'admin_updated_reference';

    public const ACTION_ADMIN_CREATED_REFERENCE = 'admin_created_reference';

    public const ACTION_ORDER_SERIES_SETTINGS_UPDATED = 'order_series_settings_updated';

    public const ACTION_POLICE_VERIFICATION_CREATED = 'police_verification_created';

    public const ACTION_POLICE_VERIFICATION_UPDATED = 'police_verification_updated';

    public const ACTION_POLICE_VERIFICATION_SUBMITTED = 'police_verification_submitted';

    public const ACTION_POLICE_VERIFICATION_UNDER_REVIEW = 'police_verification_under_review';

    public const ACTION_POLICE_VERIFICATION_COMPLETED = 'police_verification_completed';

    public const ACTION_POLICE_VERIFICATION_REJECTED = 'police_verification_rejected';

    public const ACTION_POLICE_VERIFICATION_NEED_MORE_DOCUMENTS = 'police_verification_need_more_documents';

    public const ACTION_POLICE_ACKNOWLEDGEMENT_UPLOADED = 'police_acknowledgement_uploaded';

    public const ACTION_POLICE_ACKNOWLEDGEMENT_DOWNLOADED = 'police_acknowledgement_downloaded';

    public const ACTION_POLICE_CERTIFICATE_UPLOADED = 'police_certificate_uploaded';

    public const ACTION_POLICE_CERTIFICATE_DOWNLOADED = 'police_certificate_downloaded';

    public const ACTION_BADGE_ISSUED = 'badge_issued';

    public const ACTION_BADGE_DOWNLOADED = 'badge_downloaded';

    public const ACTION_BADGE_REVOKED = 'badge_revoked';

    public const ACTION_BADGE_RENEWED = 'badge_renewed';

    /**
     * @param  array<string, mixed>  $data
     */
    public static function log(array $data, ?Request $request = null): TvAuditLog
    {
        $context = self::requestContext($request);

        return TvAuditLog::create([
            'order_id' => $data['order_id'] ?? null,
            'admin_id' => $data['admin_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'action' => $data['action'],
            'description' => $data['description'] ?? null,
            'ip' => $data['ip'] ?? $context['ip'],
            'user_agent' => $data['user_agent'] ?? $context['user_agent'],
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public static function logAdmin(Request $request, TvOrder $order, string $action, ?string $description = null, ?array $metadata = null): TvAuditLog
    {
        return self::log([
            'order_id' => $order->id,
            'admin_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ], $request);
    }

    public static function logCustomer(Request $request, TvOrder $order, string $action, ?string $description = null, ?array $metadata = null, ?int $customerId = null): TvAuditLog
    {
        return self::log([
            'order_id' => $order->id,
            'customer_id' => $customerId ?? $order->customer_id,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ], $request);
    }

    public static function logAdminDownloadedDocument(Request $request, TvOrder $order, TvOrderDocument $document): TvAuditLog
    {
        return self::logAdmin(
            $request,
            $order,
            self::ACTION_ADMIN_DOWNLOADED_DOCUMENT,
            'Downloaded document: '.$document->doc_type,
            ['document_id' => $document->id, 'doc_type' => $document->doc_type]
        );
    }

    /** @return array{ip: ?string, user_agent: ?string} */
    private static function requestContext(?Request $request): array
    {
        if (! $request) {
            return ['ip' => null, 'user_agent' => null];
        }

        return [
            'ip' => $request->ip(),
            'user_agent' => TrustVerificationConsentService::truncateUserAgent($request->userAgent()),
        ];
    }
}

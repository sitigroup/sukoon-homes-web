<?php

namespace App\Plugins\TrustVerification\Services;

use App\Models\Customer;
use App\Plugins\TrustVerification\Models\TvCheckItem;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvPackage;
use App\Plugins\TrustVerification\Models\TvPoliceVerification;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class TrustVerificationPoliceVerificationService
{
    public const PROVIDER_RAJASTHAN = 'rajasthan_police';

    public const RAJASTHAN_STATUS_CHECK_URL = 'https://citizenapp.rajasthan.gov.in/';

    /** @var list<string> */
    public const POLICE_CHECK_KEYS = [
        'criminal_check',
        'police_verification',
        'tenant_verification',
    ];

    /** @var list<string> */
    public const STATUSES = [
        'not_submitted',
        'submitted',
        'under_review',
        'completed',
        'rejected',
        'need_more_documents',
    ];

    /** @var list<string> */
    public const VERIFICATION_TYPES = ['tenant', 'owner', 'other'];

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        'not_submitted' => 'Not submitted',
        'submitted' => 'Submitted',
        'under_review' => 'Under review',
        'completed' => 'Completed',
        'rejected' => 'Rejected',
        'need_more_documents' => 'Need more documents',
    ];

    public static function orderRequiresPoliceVerification(TvOrder $order): bool
    {
        $order->loadMissing(['checkItems', 'package']);

        foreach (self::POLICE_CHECK_KEYS as $key) {
            $item = $order->checkItems->firstWhere('check_key', $key);
            if ($item && $item->status !== 'na') {
                return true;
            }
        }

        return self::packageRequiresPoliceVerification($order->package);
    }

    public static function packageRequiresPoliceVerification(?TvPackage $package): bool
    {
        if (! $package) {
            return false;
        }

        $features = collect($package->features ?? []);
        $keywords = ['police', 'criminal', 'civil'];

        return $features->contains(function ($feature) use ($keywords) {
            if (! ($feature['included'] ?? false)) {
                return false;
            }

            $key = strtolower((string) ($feature['key'] ?? ''));
            $label = strtolower((string) ($feature['label'] ?? ''));

            foreach (self::POLICE_CHECK_KEYS as $checkKey) {
                if ($key === $checkKey) {
                    return true;
                }
            }

            foreach ($keywords as $word) {
                if (str_contains($key, $word) || str_contains($label, $word)) {
                    return true;
                }
            }

            return false;
        });
    }

    public static function defaultVerificationType(TvOrder $order): string
    {
        return match ($order->order_type) {
            'owner' => 'owner',
            'tenant' => 'tenant',
            default => 'other',
        };
    }

    public static function getForOrder(TvOrder $order): ?TvPoliceVerification
    {
        $order->loadMissing('policeVerification');

        return $order->policeVerification;
    }

    public static function getOrCreateForOrder(TvOrder $order): TvPoliceVerification
    {
        $existing = self::getForOrder($order);
        if ($existing) {
            return $existing;
        }

        return TvPoliceVerification::create([
            'order_id' => $order->id,
            'provider' => self::PROVIDER_RAJASTHAN,
            'verification_type' => self::defaultVerificationType($order),
            'status' => 'not_submitted',
            'state' => 'Rajasthan',
            'status_check_url' => self::RAJASTHAN_STATUS_CHECK_URL,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function upsertFromCustomer(
        TvOrder $order,
        array $data,
        ?UploadedFile $acknowledgement = null,
        ?Request $request = null
    ): TvPoliceVerification {
        if (! self::orderRequiresPoliceVerification($order)) {
            throw new InvalidArgumentException('Police verification is not included in this order package.');
        }

        if (! in_array($order->status, ['submitted', 'in_progress'], true)) {
            throw new InvalidArgumentException('Police verification cannot be updated for this order status.');
        }

        $validator = Validator::make($data, [
            'police_station_name' => 'required|string|max:160',
            'city' => 'required|string|max:80',
            'district' => 'required|string|max:80',
            'applicant_mobile' => 'required|string|max:20',
            'reference_number' => 'nullable|string|max:64',
            'customer_notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        $payload = $validator->validated();
        $previousStatus = null;

        return DB::transaction(function () use ($order, $payload, $acknowledgement, $request, &$previousStatus) {
            $record = self::getOrCreateForOrder($order);
            $previousStatus = $record->status;

            if (in_array($record->status, ['completed'], true)) {
                throw new InvalidArgumentException('Police verification is already completed.');
            }

            $record->fill([
                'police_station_name' => $payload['police_station_name'],
                'city' => $payload['city'],
                'district' => $payload['district'],
                'applicant_mobile' => $payload['applicant_mobile'],
                'reference_number' => $payload['reference_number'] ?? $record->reference_number,
                'customer_notes' => $payload['customer_notes'] ?? null,
                'updated_by' => null,
            ]);

            if ($record->status === 'not_submitted') {
                $record->status = 'submitted';
                $record->submitted_at = $record->submitted_at ?? now();
            }

            $record->save();

            if ($acknowledgement) {
                TrustVerificationPoliceDocumentService::storeAcknowledgement(
                    $record,
                    $acknowledgement,
                    $request?->user()
                );
                $record = $record->fresh();

                TrustVerificationAuditLogService::logCustomer(
                    $request,
                    $order,
                    TrustVerificationAuditLogService::ACTION_POLICE_ACKNOWLEDGEMENT_UPLOADED,
                    'Customer uploaded police verification acknowledgement',
                    ['police_verification_id' => $record->id]
                );
            }

            self::syncCriminalCheckItem($order->fresh(['checkItems', 'policeVerification']));

            $action = $previousStatus === 'not_submitted'
                ? TrustVerificationAuditLogService::ACTION_POLICE_VERIFICATION_SUBMITTED
                : TrustVerificationAuditLogService::ACTION_POLICE_VERIFICATION_UPDATED;

            TrustVerificationAuditLogService::logCustomer(
                $request,
                $order,
                $action,
                'Customer updated police verification details',
                [
                    'police_verification_id' => $record->id,
                    'old_status' => $previousStatus,
                    'new_status' => $record->status,
                    'reference_number' => $record->reference_number,
                ]
            );

            return $record->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function updateFromAdmin(
        TvPoliceVerification $record,
        array $data,
        ?UploadedFile $acknowledgement = null,
        ?UploadedFile $certificate = null,
        ?Request $request = null
    ): TvPoliceVerification {
        $validator = Validator::make($data, [
            'verification_type' => 'nullable|in:'.implode(',', self::VERIFICATION_TYPES),
            'police_station_name' => 'nullable|string|max:160',
            'city' => 'nullable|string|max:80',
            'district' => 'nullable|string|max:80',
            'state' => 'nullable|string|max:80',
            'applicant_mobile' => 'nullable|string|max:20',
            'reference_number' => 'nullable|string|max:64',
            'status_check_url' => 'nullable|url|max:500',
            'provider_reference_number' => 'nullable|string|max:64',
            'provider_status' => 'nullable|string|max:64',
            'admin_notes' => 'nullable|string|max:5000',
            'officer_notes' => 'nullable|string|max:5000',
            'rejection_reason' => 'nullable|string|max:2000',
            'customer_notes' => 'nullable|string|max:2000',
            'submitted_at' => 'nullable|date',
            'reviewed_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'rejected_at' => 'nullable|date',
            'status' => 'nullable|in:'.implode(',', self::STATUSES),
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        $payload = $validator->validated();
        $previousStatus = $record->status;

        foreach ([
            'verification_type', 'police_station_name', 'city', 'district', 'state',
            'applicant_mobile', 'reference_number', 'status_check_url',
            'provider_reference_number', 'provider_status',
            'admin_notes', 'officer_notes', 'rejection_reason', 'customer_notes',
        ] as $field) {
            if (array_key_exists($field, $payload)) {
                $record->{$field} = $payload[$field];
            }
        }

        foreach (['submitted_at', 'reviewed_at', 'completed_at', 'rejected_at'] as $field) {
            if (array_key_exists($field, $payload)) {
                $record->{$field} = $payload[$field] ?: null;
            }
        }

        if (! empty($payload['status']) && $payload['status'] !== $record->status) {
            self::applyStatusTransition($record, $payload['status']);
        }

        $record->updated_by = Auth::id();
        $record->save();

        if ($acknowledgement) {
            TrustVerificationPoliceDocumentService::storeAcknowledgement($record, $acknowledgement, null, true);
            $record = $record->fresh();
            TrustVerificationAuditLogService::logAdmin(
                $request,
                $record->order,
                TrustVerificationAuditLogService::ACTION_POLICE_ACKNOWLEDGEMENT_UPLOADED,
                'Admin uploaded police verification acknowledgement',
                ['police_verification_id' => $record->id]
            );
        }

        if ($certificate) {
            TrustVerificationPoliceDocumentService::storeCertificate($record, $certificate);
            $record = $record->fresh();
            TrustVerificationAuditLogService::logAdmin(
                $request,
                $record->order,
                TrustVerificationAuditLogService::ACTION_POLICE_CERTIFICATE_UPLOADED,
                'Admin uploaded police verification certificate',
                ['police_verification_id' => $record->id]
            );
        }

        $order = $record->order;
        self::syncCriminalCheckItem($order->fresh(['checkItems', 'policeVerification']));

        TrustVerificationAuditLogService::logAdmin(
            $request,
            $order,
            TrustVerificationAuditLogService::ACTION_POLICE_VERIFICATION_UPDATED,
            'Police verification record updated',
            [
                'police_verification_id' => $record->id,
                'old_status' => $previousStatus,
                'new_status' => $record->status,
                'reference_number' => $record->reference_number,
            ]
        );

        TrustVerificationTrustBadgeService::onOrderEvent($order->fresh(), 'police_updated');

        return $record->fresh();
    }

    public static function transitionStatus(
        TvPoliceVerification $record,
        string $status,
        ?Request $request = null
    ): TvPoliceVerification {
        if (! in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid police verification status.');
        }

        $previousStatus = $record->status;
        self::applyStatusTransition($record, $status);
        $record->updated_by = Auth::id();
        $record->save();

        $order = $record->order;
        self::syncCriminalCheckItem($order->fresh(['checkItems', 'policeVerification']));

        $action = match ($status) {
            'submitted' => TrustVerificationAuditLogService::ACTION_POLICE_VERIFICATION_SUBMITTED,
            'under_review' => TrustVerificationAuditLogService::ACTION_POLICE_VERIFICATION_UNDER_REVIEW,
            'completed' => TrustVerificationAuditLogService::ACTION_POLICE_VERIFICATION_COMPLETED,
            'rejected' => TrustVerificationAuditLogService::ACTION_POLICE_VERIFICATION_REJECTED,
            'need_more_documents' => TrustVerificationAuditLogService::ACTION_POLICE_VERIFICATION_NEED_MORE_DOCUMENTS,
            default => TrustVerificationAuditLogService::ACTION_POLICE_VERIFICATION_UPDATED,
        };

        TrustVerificationAuditLogService::logAdmin(
            $request,
            $order,
            $action,
            'Police verification status changed to '.$status,
            [
                'police_verification_id' => $record->id,
                'old_status' => $previousStatus,
                'new_status' => $status,
                'reference_number' => $record->reference_number,
            ]
        );

        TrustVerificationTrustBadgeService::onOrderEvent($order->fresh(), 'police_'.$status);

        return $record->fresh();
    }

    private static function applyStatusTransition(TvPoliceVerification $record, string $status): void
    {
        $record->status = $status;

        match ($status) {
            'submitted' => $record->submitted_at = $record->submitted_at ?? now(),
            'under_review' => $record->reviewed_at = $record->reviewed_at ?? now(),
            'completed' => $record->completed_at = $record->completed_at ?? now(),
            'rejected' => $record->rejected_at = $record->rejected_at ?? now(),
            default => null,
        };
    }

    public static function syncCriminalCheckItem(TvOrder $order): void
    {
        if (! self::orderRequiresPoliceVerification($order)) {
            return;
        }

        $order->loadMissing('policeVerification');
        $record = $order->policeVerification;
        if (! $record) {
            return;
        }

        $check = $order->checkItems->firstWhere('check_key', 'criminal_check');
        if (! $check || $check->status === 'na') {
            return;
        }

        match ($record->status) {
            'completed' => self::setCheck($check, 'pass', 'Rajasthan Police verification completed.'),
            'rejected' => self::setCheck($check, 'fail', $record->rejection_reason ?: 'Police verification rejected.'),
            'submitted', 'under_review' => self::setCheck(
                $check,
                'pending',
                'Rajasthan Police verification '.$record->status.'.'
            ),
            'need_more_documents' => self::setCheck(
                $check,
                'pending',
                'Police verification — additional documents required.'
            ),
            default => null,
        };
    }

    private static function setCheck(TvCheckItem $check, string $status, string $notes): void
    {
        $check->status = $status;
        $check->notes = $notes;
        if ($status === 'pass') {
            $check->completed_at = $check->completed_at ?? now();
        }
        $check->save();
    }

    /**
     * @return array<string, mixed>
     */
    public static function summaryForCustomer(TvOrder $order): ?array
    {
        if (! self::orderRequiresPoliceVerification($order)) {
            return null;
        }

        $order->loadMissing('policeVerification');
        $record = $order->policeVerification;

        if (! $record) {
            return [
                'status' => 'not_submitted',
                'label' => self::STATUS_LABELS['not_submitted'],
                'provider' => self::PROVIDER_RAJASTHAN,
                'police_station_name' => null,
                'city' => null,
                'district' => null,
                'reference_number' => null,
                'submitted_at' => null,
                'completed_at' => null,
                'rejected_at' => null,
                'admin_message' => null,
                'can_upload_acknowledgement' => in_array($order->status, ['submitted', 'in_progress'], true),
                'has_acknowledgement' => false,
                'has_certificate' => false,
                'can_download_acknowledgement' => false,
                'can_download_certificate' => false,
            ];
        }

        $hasAck = TrustVerificationPoliceDocumentService::acknowledgementExists($record);
        $hasCert = TrustVerificationPoliceDocumentService::certificateExists($record);
        $canUpload = in_array($order->status, ['submitted', 'in_progress'], true)
            && ! in_array($record->status, ['completed'], true);

        return [
            'status' => $record->status,
            'label' => self::STATUS_LABELS[$record->status] ?? $record->status,
            'provider' => $record->provider,
            'verification_type' => $record->verification_type,
            'police_station_name' => $record->police_station_name,
            'city' => $record->city,
            'district' => $record->district,
            'state' => $record->state,
            'applicant_mobile' => self::maskMobile($record->applicant_mobile),
            'reference_number' => $record->reference_number,
            'submitted_at' => optional($record->submitted_at)?->toIso8601String(),
            'reviewed_at' => optional($record->reviewed_at)?->toIso8601String(),
            'completed_at' => optional($record->completed_at)?->toIso8601String(),
            'rejected_at' => optional($record->rejected_at)?->toIso8601String(),
            'admin_message' => $record->rejection_reason ?: ($record->status === 'need_more_documents' ? $record->admin_notes : null),
            'customer_notes' => $record->customer_notes,
            'can_upload_acknowledgement' => $canUpload,
            'has_acknowledgement' => $hasAck,
            'has_certificate' => $hasCert,
            'can_download_acknowledgement' => $hasAck,
            'can_download_certificate' => $hasCert && $record->status === 'completed',
            'status_check_url' => $record->status_check_url ?: self::RAJASTHAN_STATUS_CHECK_URL,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatForAdmin(TvPoliceVerification $record): array
    {
        return array_merge(self::summaryForCustomer($record->order) ?? [], [
            'id' => $record->id,
            'admin_notes' => $record->admin_notes,
            'officer_notes' => $record->officer_notes,
            'rejection_reason' => $record->rejection_reason,
            'provider_reference_number' => $record->provider_reference_number,
            'provider_status' => $record->provider_status,
            'provider_last_checked_at' => optional($record->provider_last_checked_at)?->toIso8601String(),
            'applicant_mobile' => $record->applicant_mobile,
            'acknowledgement_original_name' => $record->acknowledgement_original_name,
            'certificate_original_name' => $record->certificate_original_name,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function formatForReport(TvOrder $order): ?array
    {
        if (! self::orderRequiresPoliceVerification($order)) {
            return null;
        }

        $order->loadMissing('policeVerification');
        $record = $order->policeVerification;
        if (! $record || $record->status === 'not_submitted') {
            return null;
        }

        return [
            'status' => $record->status,
            'status_label' => self::STATUS_LABELS[$record->status] ?? $record->status,
            'reference_number' => $record->reference_number,
            'police_station_name' => $record->police_station_name,
            'completed_at' => optional($record->completed_at)?->toIso8601String(),
            'disclaimer' => 'Manual verification record maintained by Sukoon Homes.',
        ];
    }

    public static function submittedAt(TvOrder $order): ?\Carbon\Carbon
    {
        $record = self::getForOrder($order);

        return $record?->submitted_at;
    }

    public static function completedAt(TvOrder $order): ?\Carbon\Carbon
    {
        $record = self::getForOrder($order);

        return $record?->completed_at;
    }

    public static function timelineDetail(TvOrder $order): ?string
    {
        if (! self::orderRequiresPoliceVerification($order)) {
            return null;
        }

        $summary = self::summaryForCustomer($order);
        if (! $summary) {
            return null;
        }

        return match ($summary['status']) {
            'submitted' => 'Police verification submitted',
            'under_review' => 'Police verification under review',
            'completed' => 'Police verification completed',
            'rejected' => 'Police verification rejected',
            'need_more_documents' => 'Police verification — more documents needed',
            default => null,
        };
    }

    private static function maskMobile(?string $mobile): ?string
    {
        if (! $mobile) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $mobile);
        if (strlen($digits) < 4) {
            return str_repeat('*', strlen($digits));
        }

        return str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -4);
    }
}

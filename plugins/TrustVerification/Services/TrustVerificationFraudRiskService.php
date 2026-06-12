<?php

namespace App\Plugins\TrustVerification\Services;

use App\Models\Customer;
use App\Plugins\TrustVerification\Models\TvAuditLog;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvRiskProfile;
use App\Plugins\TrustVerification\Models\TvRiskSignal;
use App\Plugins\TrustVerification\Models\TvSubject;
use App\Plugins\TrustVerification\Models\TvTrustScore;
use Illuminate\Support\Facades\Schema;

class TrustVerificationFraudRiskService
{
    public const SOURCE_AUTO = 'auto';

    public const SOURCE_MANUAL = 'manual';

    public const SIGNAL_DUPLICATE_MOBILE = 'duplicate_mobile';

    public const SIGNAL_DUPLICATE_ID = 'duplicate_id';

    public const SIGNAL_TOO_MANY_REQUESTS = 'too_many_verification_requests';

    public const SIGNAL_REJECTED_DOCUMENT = 'rejected_document_upload';

    public const SIGNAL_POLICE_REJECTED = 'police_verification_rejected';

    public const SIGNAL_REFERENCE_FAILED = 'reference_failed';

    public const SIGNAL_SAME_DEVICE = 'multiple_accounts_same_device';

    public const SIGNAL_FREQUENT_EDITS = 'frequent_profile_edits';

    public const SIGNAL_ADMIN_MANUAL = 'admin_manual_flag';

    /** @var array<string, int> */
    public const DEFAULT_POINTS = [
        self::SIGNAL_DUPLICATE_MOBILE => 20,
        self::SIGNAL_DUPLICATE_ID => 40,
        self::SIGNAL_TOO_MANY_REQUESTS => 15,
        self::SIGNAL_REJECTED_DOCUMENT => 10,
        self::SIGNAL_POLICE_REJECTED => 30,
        self::SIGNAL_REFERENCE_FAILED => 20,
        self::SIGNAL_SAME_DEVICE => 35,
        self::SIGNAL_FREQUENT_EDITS => 5,
    ];

    public const SETTING_MAX_ORDERS_30D = 'risk_max_orders_30_days';

    public const SETTING_FREQUENT_EDITS_THRESHOLD = 'risk_frequent_edits_threshold';

    public static function onOrderEvent(TvOrder $order, string $event): void
    {
        if (! $order->customer_id) {
            return;
        }

        try {
            self::refreshCustomer((int) $order->customer_id, $event, $order);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function refreshCustomer(int $customerId, ?string $trigger = null, ?TvOrder $contextOrder = null): TvRiskProfile
    {
        TvRiskSignal::query()
            ->where('customer_id', $customerId)
            ->where('source', self::SOURCE_AUTO)
            ->delete();

        foreach (self::detectAutoSignals($customerId, $contextOrder) as $signal) {
            TvRiskSignal::create($signal);
        }

        return self::calculateProfile($customerId, $trigger);
    }

    public static function calculateProfile(int $customerId, ?string $trigger = null): TvRiskProfile
    {
        $signalSum = (int) TvRiskSignal::query()
            ->where('customer_id', $customerId)
            ->sum('risk_points');

        $profile = TvRiskProfile::firstOrNew(['customer_id' => $customerId]);
        $override = (int) ($profile->manual_override ?? 0);
        $score = self::clampScore($signalSum + $override);
        $level = self::levelFromScore($score);

        $profile->risk_score = $score;
        $profile->risk_level = $level;
        $profile->last_calculated_at = now();
        $profile->save();

        return $profile->fresh();
    }

    public static function manualOverride(int $customerId, int $override, ?string $notes = null): TvRiskProfile
    {
        $profile = TvRiskProfile::firstOrNew(['customer_id' => $customerId]);
        $profile->manual_override = $override;
        if ($notes !== null) {
            $profile->notes = $notes;
        }
        $profile->save();

        return self::calculateProfile($customerId, 'manual_override');
    }

    public static function addManualFlag(
        int $customerId,
        int $riskPoints,
        string $notes,
        ?int $orderId = null,
        ?int $adminId = null
    ): TvRiskSignal {
        $points = self::clampScore($riskPoints);

        $signal = TvRiskSignal::create([
            'order_id' => $orderId,
            'customer_id' => $customerId,
            'signal_type' => self::SIGNAL_ADMIN_MANUAL,
            'risk_points' => $points,
            'source' => self::SOURCE_MANUAL,
            'notes' => $notes,
            'metadata_json' => [
                'admin_id' => $adminId,
                'flagged_at' => now()->toIso8601String(),
            ],
        ]);

        self::calculateProfile($customerId, 'admin_manual_flag');

        return $signal;
    }

    public static function removeManualSignal(TvRiskSignal $signal): bool
    {
        if ($signal->source !== self::SOURCE_MANUAL) {
            return false;
        }

        $customerId = (int) $signal->customer_id;
        $signal->delete();
        self::calculateProfile($customerId, 'manual_signal_removed');

        return true;
    }

    public static function bulkRefreshAll(): int
    {
        $ids = TvOrder::query()
            ->whereNotNull('customer_id')
            ->distinct()
            ->pluck('customer_id');

        $count = 0;
        foreach ($ids as $customerId) {
            self::refreshCustomer((int) $customerId, 'bulk_refresh');
            $count++;
        }

        return $count;
    }

    public static function formatProfileForAdmin(?TvRiskProfile $profile, int $customerId): array
    {
        if (! $profile) {
            $profile = TvRiskProfile::where('customer_id', $customerId)->first();
        }

        $signals = TvRiskSignal::query()
            ->where('customer_id', $customerId)
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $trustScore = TvTrustScore::where('customer_id', $customerId)->value('trust_score');

        return [
            'customer_id' => $customerId,
            'risk_score' => $profile?->risk_score ?? 0,
            'risk_level' => $profile?->risk_level ?? 'low',
            'manual_override' => $profile?->manual_override ?? 0,
            'notes' => $profile?->notes,
            'trust_score' => $trustScore,
            'signal_count' => $signals->count(),
            'signals' => $signals->map(fn (TvRiskSignal $s) => self::formatSignal($s))->all(),
            'last_calculated_at' => $profile?->last_calculated_at?->toIso8601String(),
        ];
    }

    public static function formatSignal(TvRiskSignal $signal): array
    {
        return [
            'id' => $signal->id,
            'order_id' => $signal->order_id,
            'signal_type' => $signal->signal_type,
            'signal_label' => self::signalLabel($signal->signal_type),
            'risk_points' => $signal->risk_points,
            'source' => $signal->source,
            'notes' => $signal->notes,
            'metadata' => $signal->metadata_json,
            'created_at' => $signal->created_at?->toIso8601String(),
        ];
    }

    public static function signalLabel(string $type): string
    {
        return match ($type) {
            self::SIGNAL_DUPLICATE_MOBILE => 'Duplicate mobile',
            self::SIGNAL_DUPLICATE_ID => 'Duplicate Aadhaar / ID',
            self::SIGNAL_TOO_MANY_REQUESTS => 'Too many verification requests',
            self::SIGNAL_REJECTED_DOCUMENT => 'Rejected document upload',
            self::SIGNAL_POLICE_REJECTED => 'Police verification rejected',
            self::SIGNAL_REFERENCE_FAILED => 'Reference failed',
            self::SIGNAL_SAME_DEVICE => 'Multiple accounts same device',
            self::SIGNAL_FREQUENT_EDITS => 'Frequent profile edits',
            self::SIGNAL_ADMIN_MANUAL => 'Admin manual flag',
            default => str_replace('_', ' ', $type),
        };
    }

    public static function levelFromScore(int $score): string
    {
        $score = self::clampScore($score);

        if ($score <= 20) {
            return 'low';
        }
        if ($score <= 40) {
            return 'medium';
        }
        if ($score <= 70) {
            return 'high';
        }

        return 'critical';
    }

    public static function levelBadgeClass(string $level): string
    {
        return match ($level) {
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            default => 'secondary',
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected static function detectAutoSignals(int $customerId, ?TvOrder $contextOrder = null): array
    {
        $signals = [];
        $orders = TvOrder::query()
            ->where('customer_id', $customerId)
            ->with(['subject', 'referenceContacts', 'policeVerification', 'checkItems', 'documents'])
            ->orderByDesc('id')
            ->get();

        foreach ($orders as $order) {
            $signals = array_merge($signals, self::detectOrderSignals($customerId, $order));
        }

        $signals = array_merge($signals, self::detectCustomerWideSignals($customerId, $orders, $contextOrder));

        return self::dedupeSignals($signals);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected static function detectOrderSignals(int $customerId, TvOrder $order): array
    {
        $signals = [];
        $orderId = (int) $order->id;

        if ($order->policeVerification && $order->policeVerification->status === 'rejected') {
            $signals[] = self::signalPayload(
                $customerId,
                self::SIGNAL_POLICE_REJECTED,
                self::DEFAULT_POINTS[self::SIGNAL_POLICE_REJECTED],
                $orderId,
                'Police verification rejected on order '.$order->order_number,
                ['order_number' => $order->order_number, 'status' => 'rejected']
            );
        }

        $refs = $order->referenceContacts ?? collect();
        $failedRefs = $refs->whereIn('status', ['failed', 'no_response']);
        if ($failedRefs->isNotEmpty()) {
            $signals[] = self::signalPayload(
                $customerId,
                self::SIGNAL_REFERENCE_FAILED,
                self::DEFAULT_POINTS[self::SIGNAL_REFERENCE_FAILED],
                $orderId,
                $failedRefs->count().' reference(s) failed on order '.$order->order_number,
                ['order_number' => $order->order_number, 'failed_count' => $failedRefs->count()]
            );
        }

        if (self::orderHasRejectedDocuments($order)) {
            $signals[] = self::signalPayload(
                $customerId,
                self::SIGNAL_REJECTED_DOCUMENT,
                self::DEFAULT_POINTS[self::SIGNAL_REJECTED_DOCUMENT],
                $orderId,
                'Document rejection or failed document checks on order '.$order->order_number,
                ['order_number' => $order->order_number]
            );
        }

        return $signals;
    }

    /**
     * @param \Illuminate\Support\Collection<int, TvOrder> $orders
     * @return list<array<string, mixed>>
     */
    protected static function detectCustomerWideSignals(int $customerId, $orders, ?TvOrder $contextOrder = null): array
    {
        $signals = [];

        if ($dupMobile = self::findDuplicateMobile($customerId, $orders)) {
            $signals[] = self::signalPayload(
                $customerId,
                self::SIGNAL_DUPLICATE_MOBILE,
                self::DEFAULT_POINTS[self::SIGNAL_DUPLICATE_MOBILE],
                $contextOrder?->id,
                $dupMobile['notes'],
                $dupMobile['metadata']
            );
        }

        if ($dupId = self::findDuplicateId($customerId, $orders)) {
            $signals[] = self::signalPayload(
                $customerId,
                self::SIGNAL_DUPLICATE_ID,
                self::DEFAULT_POINTS[self::SIGNAL_DUPLICATE_ID],
                $contextOrder?->id,
                $dupId['notes'],
                $dupId['metadata']
            );
        }

        $recentOrderCount = TvOrder::query()
            ->where('customer_id', $customerId)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $maxOrders = (int) TrustVerificationSettingsService::get(self::SETTING_MAX_ORDERS_30D, 3);
        if ($recentOrderCount > $maxOrders) {
            $signals[] = self::signalPayload(
                $customerId,
                self::SIGNAL_TOO_MANY_REQUESTS,
                self::DEFAULT_POINTS[self::SIGNAL_TOO_MANY_REQUESTS],
                null,
                $recentOrderCount.' verification requests in the last 30 days (limit '.$maxOrders.')',
                ['count_30d' => $recentOrderCount, 'limit' => $maxOrders]
            );
        }

        if ($device = self::findSameDeviceCustomers($customerId)) {
            $signals[] = self::signalPayload(
                $customerId,
                self::SIGNAL_SAME_DEVICE,
                self::DEFAULT_POINTS[self::SIGNAL_SAME_DEVICE],
                null,
                $device['notes'],
                $device['metadata']
            );
        }

        if (self::hasFrequentEdits($customerId)) {
            $signals[] = self::signalPayload(
                $customerId,
                self::SIGNAL_FREQUENT_EDITS,
                self::DEFAULT_POINTS[self::SIGNAL_FREQUENT_EDITS],
                null,
                'High verification activity or repeated order updates in the last 7 days',
                ['window_days' => 7]
            );
        }

        return $signals;
    }

    protected static function orderHasRejectedDocuments(TvOrder $order): bool
    {
        $uploadRejected = TvAuditLog::query()
            ->where('order_id', $order->id)
            ->where('action', TrustVerificationAuditLogService::ACTION_CUSTOMER_UPLOAD_REJECTED)
            ->exists();

        if ($uploadRejected) {
            return true;
        }

        $docDeleted = TvAuditLog::query()
            ->where('order_id', $order->id)
            ->whereIn('action', [
                TrustVerificationAuditLogService::ACTION_ADMIN_DELETED_DOCUMENTS,
                TrustVerificationAuditLogService::ACTION_SYSTEM_DELETED_DOCUMENTS,
            ])
            ->exists();

        if ($docDeleted) {
            return true;
        }

        return $order->checkItems
            ->whereIn('check_key', ['id_verification', 'document_review', 'documents'])
            ->contains(fn ($item) => $item->status === 'fail');
    }

    /**
     * @param \Illuminate\Support\Collection<int, TvOrder> $orders
     * @return array{notes: string, metadata: array}|null
     */
    protected static function findDuplicateMobile(int $customerId, $orders): ?array
    {
        $phones = [];
        foreach ($orders as $order) {
            if ($order->requester_phone) {
                $phones[] = self::normalizePhone($order->requester_phone);
            }
            if ($order->subject?->phone) {
                $phones[] = self::normalizePhone($order->subject->phone);
            }
        }

        $phones = array_values(array_unique(array_filter($phones)));
        if ($phones === []) {
            return null;
        }

        $otherCustomerIds = [];
        foreach ($phones as $phone) {
            $subjectMatches = TvSubject::query()
                ->whereHas('order', fn ($q) => $q->where('customer_id', '!=', $customerId))
                ->where(function ($q) use ($phone) {
                    $q->where('phone', 'like', '%'.$phone.'%')
                        ->orWhereRaw('REPLACE(REPLACE(REPLACE(phone, " ", ""), "-", ""), "+", "") LIKE ?', ['%'.$phone.'%']);
                })
                ->with('order:id,customer_id')
                ->get()
                ->pluck('order.customer_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $orderMatches = TvOrder::query()
                ->where('customer_id', '!=', $customerId)
                ->where(function ($q) use ($phone) {
                    $q->where('requester_phone', 'like', '%'.$phone.'%')
                        ->orWhereRaw('REPLACE(REPLACE(REPLACE(requester_phone, " ", ""), "-", ""), "+", "") LIKE ?', ['%'.$phone.'%']);
                })
                ->pluck('customer_id')
                ->unique()
                ->all();

            $otherCustomerIds = array_merge($otherCustomerIds, $subjectMatches, $orderMatches);
        }

        if (class_exists(Customer::class) && Schema::hasTable('customers')) {
            $column = Schema::hasColumn('customers', 'mobile') ? 'mobile' : (Schema::hasColumn('customers', 'phone') ? 'phone' : null);
            if ($column) {
                foreach ($phones as $phone) {
                    $platformIds = Customer::query()
                        ->where('id', '!=', $customerId)
                        ->where(function ($q) use ($column, $phone) {
                            $q->where($column, 'like', '%'.$phone.'%');
                        })
                        ->pluck('id')
                        ->all();
                    $otherCustomerIds = array_merge($otherCustomerIds, $platformIds);
                }
            }
        }

        $otherCustomerIds = array_values(array_unique(array_filter($otherCustomerIds)));
        if ($otherCustomerIds === []) {
            return null;
        }

        return [
            'notes' => 'Mobile number matches '.count($otherCustomerIds).' other customer account(s)',
            'metadata' => [
                'phones' => $phones,
                'other_customer_ids' => array_slice($otherCustomerIds, 0, 20),
            ],
        ];
    }

    /**
     * @param \Illuminate\Support\Collection<int, TvOrder> $orders
     * @return array{notes: string, metadata: array}|null
     */
    protected static function findDuplicateId(int $customerId, $orders): ?array
    {
        $pairs = [];
        foreach ($orders as $order) {
            $subject = $order->subject;
            if (! $subject?->id_number_hint || ! $subject?->id_type) {
                continue;
            }
            $key = strtolower(trim($subject->id_type)).'|'.trim($subject->id_number_hint);
            $pairs[$key] = ['id_type' => $subject->id_type, 'id_number_hint' => $subject->id_number_hint];
        }

        if ($pairs === []) {
            return null;
        }

        $otherIds = [];
        foreach ($pairs as $key => $pair) {
            $matches = TvSubject::query()
                ->where('id_type', $pair['id_type'])
                ->where('id_number_hint', $pair['id_number_hint'])
                ->whereHas('order', fn ($q) => $q->where('customer_id', '!=', $customerId))
                ->with('order:id,customer_id')
                ->get()
                ->pluck('order.customer_id')
                ->filter()
                ->unique()
                ->values()
                ->all();
            $otherIds = array_merge($otherIds, $matches);
        }

        $otherIds = array_values(array_unique(array_filter($otherIds)));
        if ($otherIds === []) {
            return null;
        }

        return [
            'notes' => 'ID hint matches '.count($otherIds).' other customer account(s)',
            'metadata' => [
                'id_pairs' => array_values($pairs),
                'other_customer_ids' => array_slice($otherIds, 0, 20),
            ],
        ];
    }

    /**
     * @return array{notes: string, metadata: array}|null
     */
    protected static function findSameDeviceCustomers(int $customerId): ?array
    {
        $ips = TvOrder::query()
            ->where('customer_id', $customerId)
            ->whereNotNull('consent_ip')
            ->where('consent_ip', '!=', '')
            ->orderByDesc('id')
            ->limit(20)
            ->pluck('consent_ip')
            ->unique()
            ->filter()
            ->values();

        if ($ips->isEmpty()) {
            return null;
        }

        $otherIds = TvOrder::query()
            ->whereIn('consent_ip', $ips->all())
            ->where('customer_id', '!=', $customerId)
            ->where('created_at', '>=', now()->subDays(90))
            ->distinct()
            ->pluck('customer_id')
            ->filter()
            ->values()
            ->all();

        if ($otherIds === []) {
            return null;
        }

        return [
            'notes' => 'Same consent IP as '.count($otherIds).' other customer(s) in 90 days',
            'metadata' => [
                'ips' => $ips->take(5)->values()->all(),
                'other_customer_ids' => array_slice($otherIds, 0, 20),
            ],
        ];
    }

    protected static function hasFrequentEdits(int $customerId): bool
    {
        $threshold = (int) TrustVerificationSettingsService::get(self::SETTING_FREQUENT_EDITS_THRESHOLD, 8);

        $orderCreates = TvOrder::query()
            ->where('customer_id', $customerId)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $auditCount = TvAuditLog::query()
            ->where('customer_id', $customerId)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return ($orderCreates + $auditCount) >= $threshold;
    }

    /**
     * @param  list<array<string, mixed>>  $signals
     * @return list<array<string, mixed>>
     */
    protected static function dedupeSignals(array $signals): array
    {
        $seen = [];
        $out = [];
        foreach ($signals as $signal) {
            $key = $signal['signal_type'].'|'.($signal['order_id'] ?? 'global');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $signal;
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function signalPayload(
        int $customerId,
        string $type,
        int $points,
        ?int $orderId,
        string $notes,
        array $metadata = []
    ): array {
        return [
            'order_id' => $orderId,
            'customer_id' => $customerId,
            'signal_type' => $type,
            'risk_points' => self::clampScore($points),
            'source' => self::SOURCE_AUTO,
            'notes' => $notes,
            'metadata_json' => $metadata,
        ];
    }

    protected static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        return strlen($digits) >= 10 ? substr($digits, -10) : $digits;
    }

    protected static function clampScore(int $score): int
    {
        return max(0, min(100, $score));
    }
}

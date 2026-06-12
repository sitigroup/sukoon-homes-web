<?php

namespace App\Plugins\TrustVerification\Services;

use App\Plugins\TrustVerification\Models\TvCustomerBadge;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvSetting;
use App\Plugins\TrustVerification\Models\TvTenantReliability;
use App\Plugins\TrustVerification\Models\TvTrustScore;

class TrustVerificationTenantReliabilityService
{
    public const SETTING_PUBLIC_ENABLED = 'tenant_reliability_public_enabled';

    public const LEVEL_BASIC = 'basic_tenant';

    public const LEVEL_VERIFIED = 'verified_tenant';

    public const LEVEL_TRUSTED = 'trusted_tenant';

    public const LEVEL_PREMIUM = 'premium_trusted_tenant';

    /** @var array<string, string> */
    public const LEVEL_LABELS = [
        self::LEVEL_BASIC => 'Basic Tenant',
        self::LEVEL_VERIFIED => 'Verified Tenant',
        self::LEVEL_TRUSTED => 'Trusted Tenant',
        self::LEVEL_PREMIUM => 'Premium Trusted Tenant',
    ];

    /** Owner-safe check keys only — no PII, no risk, no admin notes. */
    public const CHECK_KEYS = [
        'identity' => 'Identity Verified',
        'reference' => 'Reference Checked',
        'police' => 'Police Verification Completed',
        'documents' => 'Documents Verified',
    ];

    public static function onOrderEvent(TvOrder $order, string $event): void
    {
        if (! $order->customer_id) {
            return;
        }

        if ($order->order_type !== 'tenant') {
            return;
        }

        try {
            self::refresh((int) $order->customer_id, $event);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function refresh(int $customerId, ?string $trigger = null): ?TvTenantReliability
    {
        $order = self::latestCompletedTenantOrder($customerId);
        if (! $order) {
            return TvTenantReliability::where('customer_id', $customerId)->first();
        }

        return self::calculateReliability($customerId, $order, $trigger);
    }

    public static function calculateReliability(int $customerId, ?TvOrder $order = null, ?string $trigger = null): ?TvTenantReliability
    {
        $order = $order ?? self::latestCompletedTenantOrder($customerId);
        if (! $order) {
            return null;
        }

        $summary = self::buildSummary($order, $customerId);
        $completion = self::verificationCompletion($summary['checks']);
        $level = self::levelFromCompletion($completion);
        $status = self::verificationStatusFromCompletion($completion);

        $record = TvTenantReliability::firstOrNew(['customer_id' => $customerId]);
        $record->verification_completion = $completion;
        $record->reliability_level = $level;
        $record->verification_status = $status;
        $record->summary_json = array_merge($summary, [
            'trigger' => $trigger,
            'calculated_at' => now()->toIso8601String(),
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);
        $record->last_calculated_at = now();
        if (! $record->exists) {
            $record->public_visible = true;
        }
        $record->save();

        return $record->fresh();
    }

    /**
     * @return array{checks: list<array{key: string, label: string, verified: bool}>, trust_verified: bool}
     */
    public static function buildSummary(TvOrder $order, int $customerId): array
    {
        $order->loadMissing(['checkItems', 'policeVerification', 'documents']);

        $badgeSlugs = TvCustomerBadge::query()
            ->where('customer_id', $customerId)
            ->whereHas('badge')
            ->with('badge')
            ->get()
            ->pluck('badge.slug')
            ->filter()
            ->values()
            ->all();

        $checks = [];
        foreach (self::CHECK_KEYS as $key => $label) {
            $checks[] = [
                'key' => $key,
                'label' => $label,
                'verified' => self::isCheckVerified($key, $order, $badgeSlugs),
            ];
        }

        $trustScore = TvTrustScore::where('customer_id', $customerId)->value('trust_score');
        $trustVerified = in_array('trusted_tenant', $badgeSlugs, true)
            || in_array('tenant_verified', $badgeSlugs, true)
            || ((int) ($trustScore ?? 0)) >= 61;

        return [
            'checks' => $checks,
            'trust_verified' => $trustVerified,
        ];
    }

    /**
     * @param  list<array{verified: bool}>  $checks
     */
    public static function verificationCompletion(array $checks): int
    {
        if ($checks === []) {
            return 0;
        }

        $total = count(self::CHECK_KEYS);
        $passed = count(array_filter($checks, fn ($c) => ! empty($c['verified'])));

        return (int) round(($passed / $total) * 100);
    }

    public static function levelFromCompletion(int $completion): string
    {
        $completion = max(0, min(100, $completion));

        if ($completion <= 30) {
            return self::LEVEL_BASIC;
        }
        if ($completion <= 60) {
            return self::LEVEL_VERIFIED;
        }
        if ($completion <= 85) {
            return self::LEVEL_TRUSTED;
        }

        return self::LEVEL_PREMIUM;
    }

    public static function levelLabel(string $level): string
    {
        return self::LEVEL_LABELS[$level] ?? 'Tenant';
    }

    public static function verificationStatusFromCompletion(int $completion): string
    {
        if ($completion >= 100) {
            return 'complete';
        }
        if ($completion > 0) {
            return 'partial';
        }

        return 'pending';
    }

    public static function isPublicEnabled(): bool
    {
        $value = TvSetting::where('key', self::SETTING_PUBLIC_ENABLED)->value('value');

        return $value === null || $value === '1' || $value === 'true';
    }

    public static function setPublicVisible(int $customerId, bool $visible): ?TvTenantReliability
    {
        $record = TvTenantReliability::where('customer_id', $customerId)->first();
        if (! $record) {
            return null;
        }
        $record->public_visible = $visible;
        $record->save();

        return $record->fresh();
    }

    /**
     * Owner-safe payload — never includes risk, PII, breakdown, or admin fields.
     */
    public static function publicSafeData(int $customerId, bool $publicRequest = true): ?array
    {
        $record = TvTenantReliability::where('customer_id', $customerId)->first();
        if (! $record) {
            $refreshed = self::refresh($customerId, 'public_read');
            $record = $refreshed;
        }

        if (! $record) {
            return null;
        }

        if ($publicRequest && (! self::isPublicEnabled() || ! $record->public_visible)) {
            return null;
        }

        $summary = is_array($record->summary_json) ? $record->summary_json : [];
        $checks = $summary['checks'] ?? [];

        return [
            'customer_id' => $customerId,
            'reliability_level' => $record->reliability_level,
            'reliability_label' => self::levelLabel($record->reliability_level),
            'verification_completion' => (int) $record->verification_completion,
            'verification_status' => $record->verification_status,
            'overall_label' => self::levelLabel($record->reliability_level),
            'checks' => array_values(array_map(fn ($c) => [
                'key' => $c['key'] ?? '',
                'label' => $c['label'] ?? '',
                'verified' => (bool) ($c['verified'] ?? false),
            ], $checks)),
            'last_calculated_at' => optional($record->last_calculated_at)?->toIso8601String(),
        ];
    }

    public static function bulkRefreshAll(): int
    {
        $count = 0;
        TvOrder::query()
            ->where('order_type', 'tenant')
            ->where('status', 'completed')
            ->whereNotNull('customer_id')
            ->select('customer_id')
            ->distinct()
            ->orderBy('customer_id')
            ->chunk(100, function ($rows) use (&$count) {
                foreach ($rows as $row) {
                    self::refresh((int) $row->customer_id, 'bulk_refresh');
                    $count++;
                }
            });

        return $count;
    }

    /**
     * @param  list<string>  $badgeSlugs
     */
    protected static function isCheckVerified(string $key, TvOrder $order, array $badgeSlugs): bool
    {
        return match ($key) {
            'identity' => self::orderCheckPassed($order, ['id_verification'])
                || in_array('identity_verified', $badgeSlugs, true),
            'reference' => self::orderCheckPassed($order, ['reference_check'])
                || in_array('reference_verified', $badgeSlugs, true),
            'police' => self::policeVerifiedForTenant($order, $badgeSlugs),
            'documents' => self::documentsVerifiedForTenant($order)
                || in_array('document_verified', $badgeSlugs, true),
            default => false,
        };
    }

    /** @param  list<string>  $keys */
    protected static function orderCheckPassed(TvOrder $order, array $keys): bool
    {
        $order->loadMissing('checkItems');
        foreach ($keys as $key) {
            $item = $order->checkItems->firstWhere('check_key', $key);
            if ($item && $item->status !== 'na' && $item->status === 'pass') {
                return true;
            }
        }

        return false;
    }

    protected static function documentsVerifiedForTenant(TvOrder $order): bool
    {
        if (self::orderCheckPassed($order, ['ownership_docs'])) {
            return true;
        }

        return TrustVerificationDocumentService::missingRequiredTypes($order) === [];
    }

    /**
     * @param  list<string>  $badgeSlugs
     */
    /**
     * @param  list<string>  $badgeSlugs
     */
    protected static function policeVerifiedForTenant(TvOrder $order, array $badgeSlugs): bool
    {
        if (in_array('police_verified', $badgeSlugs, true)) {
            return true;
        }

        if ($order->policeVerification && $order->policeVerification->status === 'completed') {
            return true;
        }

        return self::orderCheckPassed($order, ['criminal_check']);
    }

    protected static function latestCompletedTenantOrder(int $customerId): ?TvOrder
    {
        return TvOrder::query()
            ->where('customer_id', $customerId)
            ->where('order_type', 'tenant')
            ->where('status', 'completed')
            ->with(['checkItems', 'policeVerification', 'documents', 'package'])
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->first();
    }
}

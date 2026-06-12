<?php

namespace App\Plugins\TrustVerification\Services;

use App\Plugins\TrustVerification\Models\TvCheckItem;
use App\Plugins\TrustVerification\Models\TvCustomerBadge;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvSetting;
use App\Plugins\TrustVerification\Models\TvTrustBadge;
use App\Plugins\TrustVerification\Models\TvTrustScore;
use Illuminate\Support\Facades\DB;

class TrustVerificationTrustBadgeService
{
    public const SETTING_PUBLIC_ENABLED = 'trust_public_profile_enabled';

    public const SETTING_SCORE_WEIGHTS = 'trust_score_weights';

    /** @var array<string, int> */
    public const DEFAULT_WEIGHTS = [
        'identity' => 20,
        'address' => 15,
        'reference' => 20,
        'police' => 25,
        'documents' => 10,
        'admin_approval' => 5,
        'no_rejected' => 5,
    ];

    public static function refreshScore(int $customerId, ?string $trigger = null): TvTrustScore
    {
        return self::calculate($customerId, $trigger);
    }

    public static function calculate(int $customerId, ?string $trigger = null): TvTrustScore
    {
        $order = self::latestCompletedOrder($customerId);
        $weights = self::scoreWeights();
        $breakdown = self::buildBreakdown($order, $weights);
        $raw = (int) ($breakdown['raw_total'] ?? 0);

        $record = TvTrustScore::firstOrNew(['customer_id' => $customerId]);
        $adjustment = (int) ($record->manual_adjustment ?? 0);
        $final = self::clampScore($raw + $adjustment);

        $breakdown['manual_adjustment'] = $adjustment;
        $breakdown['final_score'] = $final;
        $breakdown['trigger'] = $trigger;
        $breakdown['order_id'] = $order?->id;
        $breakdown['calculated_at'] = now()->toIso8601String();

        $record->trust_score = $final;
        $record->score_breakdown_json = $breakdown;
        $record->last_calculated_at = now();
        if (! $record->exists) {
            $record->public_visible = true;
        }
        $record->save();

        self::evaluateAutoBadges($customerId, $order, $final);

        return $record->fresh();
    }

    public static function manualAdjustment(int $customerId, int $adjustment, ?int $adminUserId = null): TvTrustScore
    {
        $record = TvTrustScore::firstOrNew(['customer_id' => $customerId]);
        $record->manual_adjustment = $adjustment;
        if (! $record->exists) {
            $record->public_visible = true;
            $record->trust_score = 0;
        }
        $record->save();

        return self::calculate($customerId, 'manual_adjustment');
    }

    public static function setPublicVisible(int $customerId, bool $visible): TvTrustScore
    {
        $record = TvTrustScore::firstOrNew(['customer_id' => $customerId]);
        $record->public_visible = $visible;
        if (! $record->exists) {
            $record->trust_score = 0;
            $record->manual_adjustment = 0;
        }
        $record->save();

        return $record->fresh();
    }

    public static function assignBadge(
        int $customerId,
        int $badgeId,
        ?int $assignedBy = null,
        bool $autoAssigned = false,
        ?string $notes = null
    ): ?TvCustomerBadge {
        $badge = TvTrustBadge::where('id', $badgeId)->where('active', true)->first();
        if (! $badge) {
            return null;
        }

        return TvCustomerBadge::firstOrCreate(
            ['customer_id' => $customerId, 'badge_id' => $badgeId],
            [
                'assigned_by' => $assignedBy,
                'auto_assigned' => $autoAssigned,
                'notes' => $notes,
            ]
        );
    }

    public static function assignBadgeBySlug(
        int $customerId,
        string $slug,
        ?int $assignedBy = null,
        bool $autoAssigned = true,
        ?string $notes = null
    ): ?TvCustomerBadge {
        $badge = TvTrustBadge::where('slug', $slug)->where('active', true)->first();
        if (! $badge) {
            return null;
        }

        return self::assignBadge($customerId, (int) $badge->id, $assignedBy, $autoAssigned, $notes);
    }

    public static function removeBadge(int $customerId, int $badgeId): bool
    {
        return (bool) TvCustomerBadge::where('customer_id', $customerId)
            ->where('badge_id', $badgeId)
            ->delete();
    }

    public static function removeBadgeBySlug(int $customerId, string $slug): bool
    {
        $badge = TvTrustBadge::where('slug', $slug)->first();
        if (! $badge) {
            return false;
        }

        return self::removeBadge($customerId, (int) $badge->id);
    }

    public static function onOrderEvent(TvOrder $order, string $event): void
    {
        if (! $order->customer_id) {
            return;
        }

        try {
            self::refreshScore((int) $order->customer_id, $event);
        } catch (\Throwable $e) {
            report($e);
        }

        TrustVerificationFraudRiskService::onOrderEvent($order, $event);
        TrustVerificationTenantReliabilityService::onOrderEvent($order, $event);
        TrustVerificationIssuedBadgeService::onOrderEvent($order, $event);
    }

    public static function isPublicTrustEnabled(): bool
    {
        $value = TvSetting::where('key', self::SETTING_PUBLIC_ENABLED)->value('value');

        return $value === null || $value === '1' || $value === 'true';
    }

    /** @return array<string, int> */
    public static function scoreWeights(): array
    {
        $raw = TvSetting::where('key', self::SETTING_SCORE_WEIGHTS)->value('value');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_merge(self::DEFAULT_WEIGHTS, $decoded);
            }
        }

        return self::DEFAULT_WEIGHTS;
    }

    public static function saveScoreWeights(array $weights): void
    {
        $merged = array_merge(self::DEFAULT_WEIGHTS, $weights);
        TvSetting::updateOrCreate(
            ['key' => self::SETTING_SCORE_WEIGHTS],
            ['value' => json_encode($merged)]
        );
    }

    public static function formatForApi(int $customerId, bool $publicRequest = false): ?array
    {
        $score = TvTrustScore::where('customer_id', $customerId)->first();
        if (! $score) {
            return null;
        }

        if ($publicRequest && (! self::isPublicTrustEnabled() || ! $score->public_visible)) {
            return null;
        }

        $badges = TvCustomerBadge::with('badge')
            ->where('customer_id', $customerId)
            ->get()
            ->sortBy(fn (TvCustomerBadge $row) => $row->badge?->priority ?? 999)
            ->values();

        $payload = [
            'customer_id' => $customerId,
            'trust_score' => (int) $score->trust_score,
            'manual_adjustment' => (int) $score->manual_adjustment,
            'public_visible' => (bool) $score->public_visible,
            'last_calculated_at' => optional($score->last_calculated_at)?->toIso8601String(),
            'breakdown' => $score->score_breakdown_json ?? [],
            'badges' => $badges->map(fn (TvCustomerBadge $row) => self::formatBadgeRow($row))->all(),
        ];

        if ($publicRequest) {
            $cardFlags = TrustVerificationIssuedBadgeService::cardTrustBadgeFlags($customerId);
            $payload['owner_trust_badge'] = $cardFlags['owner_trust_badge'];
            $payload['tenant_trust_badge'] = $cardFlags['tenant_trust_badge'];
            $payload['sukoon_verified_owner'] = $cardFlags['owner_trust_badge']
                ? TrustVerificationIssuedBadgeService::publicOwnerBadgeForCustomer($customerId)
                : null;
            $payload['badges'] = self::publicCatalogBadgesForCard($badges);
        } else {
            $payload['verification_badges'] = TrustVerificationIssuedBadgeService::activeBadgesForCustomer($customerId);
        }

        return $payload;
    }

    public static function formatBadgeRow(TvCustomerBadge $row): array
    {
        $badge = $row->badge;

        return [
            'slug' => $badge?->slug,
            'name' => $badge?->name,
            'description' => $badge?->description,
            'icon' => $badge?->icon,
            'color' => $badge?->color,
            'priority' => $badge?->priority,
            'auto_assigned' => (bool) $row->auto_assigned,
            'assigned_at' => optional($row->created_at)?->toIso8601String(),
        ];
    }

    /**
     * Catalog slugs that must not drive property-card pills (use owner_trust_badge / tenant_trust_badge).
     *
     * @param  \Illuminate\Support\Collection<int, TvCustomerBadge>  $badges
     * @return list<array<string, mixed>>
     */
    protected static function publicCatalogBadgesForCard($badges): array
    {
        $blocked = ['trusted_owner', 'trusted_tenant'];

        return $badges
            ->filter(fn (TvCustomerBadge $row) => ! in_array($row->badge?->slug, $blocked, true))
            ->map(fn (TvCustomerBadge $row) => self::formatBadgeRow($row))
            ->values()
            ->all();
    }

    public static function bulkRefreshAll(): int
    {
        $count = 0;
        TvOrder::query()
            ->where('status', 'completed')
            ->whereNotNull('customer_id')
            ->select('customer_id')
            ->distinct()
            ->orderBy('customer_id')
            ->chunk(100, function ($rows) use (&$count) {
                foreach ($rows as $row) {
                    self::refreshScore((int) $row->customer_id, 'bulk_refresh');
                    $count++;
                }
            });

        return $count;
    }

    protected static function latestCompletedOrder(int $customerId): ?TvOrder
    {
        return TvOrder::query()
            ->where('customer_id', $customerId)
            ->where('status', 'completed')
            ->with(['checkItems', 'policeVerification', 'documents', 'package', 'report'])
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->first();
    }

    /** @param  array<string, int>  $weights */
    protected static function buildBreakdown(?TvOrder $order, array $weights): array
    {
        $components = [];

        $identityPass = self::checkPassed($order, ['id_verification']);
        $components['identity'] = self::component('Identity verified', $weights['identity'], $identityPass);

        $addressPass = self::checkPassed($order, ['address_validation', 'address_match']);
        $components['address'] = self::component('Address verified', $weights['address'], $addressPass);

        $referencePass = self::checkPassed($order, ['reference_check']);
        $components['reference'] = self::component('Reference verified', $weights['reference'], $referencePass);

        $policePass = self::policeVerified($order);
        $components['police'] = self::component('Police verification', $weights['police'], $policePass);

        $documentsPass = self::documentsVerified($order);
        $components['documents'] = self::component('Documents verified', $weights['documents'], $documentsPass);

        $adminPass = $order && $order->status === 'completed' && $order->report;
        $components['admin_approval'] = self::component('Admin approval', $weights['admin_approval'], (bool) $adminPass);

        $noRejected = $order && ! self::hasRejectedChecks($order);
        $components['no_rejected'] = self::component('No rejected checks', $weights['no_rejected'], $noRejected);

        $raw = 0;
        foreach ($components as $row) {
            if (! empty($row['passed'])) {
                $raw += (int) $row['points'];
            }
        }

        return [
            'components' => $components,
            'raw_total' => min(100, $raw),
        ];
    }

    /** @return array{label: string, points: int, max: int, passed: bool} */
    protected static function component(string $label, int $points, bool $passed): array
    {
        return [
            'label' => $label,
            'points' => $points,
            'max' => $points,
            'passed' => $passed,
        ];
    }

    /** @param  list<string>  $keys */
    protected static function checkPassed(?TvOrder $order, array $keys): bool
    {
        if (! $order) {
            return false;
        }

        $order->loadMissing('checkItems');

        foreach ($keys as $key) {
            $item = $order->checkItems->firstWhere('check_key', $key);
            if ($item && $item->status !== 'na' && $item->status === 'pass') {
                return true;
            }
        }

        return false;
    }

    protected static function policeVerified(?TvOrder $order): bool
    {
        if (! $order) {
            return false;
        }

        if (self::checkPassed($order, ['criminal_check'])) {
            return true;
        }

        $order->loadMissing('policeVerification');
        $pv = $order->policeVerification;

        return $pv && $pv->status === 'completed';
    }

    protected static function documentsVerified(?TvOrder $order): bool
    {
        if (! $order) {
            return false;
        }

        if (self::checkPassed($order, ['ownership_docs'])) {
            return true;
        }

        return TrustVerificationDocumentService::missingRequiredTypes($order) === [];
    }

    protected static function hasRejectedChecks(TvOrder $order): bool
    {
        return $order->checkItems->contains(fn (TvCheckItem $item) => $item->status === 'fail');
    }

    protected static function evaluateAutoBadges(int $customerId, ?TvOrder $order, int $score): void
    {
        $badges = TvTrustBadge::where('active', true)->where('auto_assign', true)->get()->keyBy('slug');
        $assignedSlugs = [];

        if (self::checkPassed($order, ['id_verification'])) {
            $assignedSlugs[] = 'identity_verified';
        }
        if (self::checkPassed($order, ['address_validation', 'address_match'])) {
            $assignedSlugs[] = 'address_verified';
        }
        if (self::checkPassed($order, ['reference_check'])) {
            $assignedSlugs[] = 'reference_verified';
        }
        if (self::policeVerified($order)) {
            $assignedSlugs[] = 'police_verified';
        }
        if (self::documentsVerified($order)) {
            $assignedSlugs[] = 'document_verified';
        }

        if ($order && $order->status === 'completed') {
            if ($order->order_type === 'tenant') {
                $assignedSlugs[] = 'tenant_verified';
            }
            if ($order->order_type === 'owner') {
                $assignedSlugs[] = 'owner_verified';
            }
        }

        if ($score >= 80 && $order?->order_type === 'tenant') {
            $assignedSlugs[] = 'trusted_tenant';
        }
        if ($score >= 80 && $order?->order_type === 'owner') {
            $assignedSlugs[] = 'trusted_owner';
        }
        if ($score >= 90) {
            $assignedSlugs[] = 'premium_verified';
        }
        if ($score >= 100 && count(array_unique($assignedSlugs)) >= 6) {
            $assignedSlugs[] = 'sukoon_elite_verified';
        }

        $assignedSlugs = array_unique($assignedSlugs);

        DB::transaction(function () use ($customerId, $badges, $assignedSlugs) {
            foreach ($assignedSlugs as $slug) {
                if ($badges->has($slug)) {
                    self::assignBadgeBySlug($customerId, $slug, null, true);
                }
            }

            TvCustomerBadge::where('customer_id', $customerId)
                ->where('auto_assigned', true)
                ->whereHas('badge', fn ($q) => $q->where('auto_assign', true))
                ->with('badge')
                ->get()
                ->each(function (TvCustomerBadge $row) use ($assignedSlugs) {
                    $slug = $row->badge?->slug;
                    if ($slug && ! in_array($slug, $assignedSlugs, true)) {
                        $row->delete();
                    }
                });
        });
    }

    protected static function clampScore(int $score): int
    {
        return max(0, min(100, $score));
    }
}

<?php

namespace App\Plugins\TrustVerification\Services;

use App\Plugins\TrustVerification\Models\TvCheckItem;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvVerificationBadge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class TrustVerificationIssuedBadgeService
{
    public const DEFAULT_VALIDITY_MONTHS = 12;

    public static function onOrderEvent(TvOrder $order, string $event): void
    {
        if (! $order->customer_id) {
            return;
        }

        try {
            self::tryAutoIssue($order->fresh());
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function tryAutoIssue(TvOrder $order): ?TvVerificationBadge
    {
        if (TvVerificationBadge::where('order_id', $order->id)->exists()) {
            $existing = TvVerificationBadge::where('order_id', $order->id)->first();
            if ($existing && $existing->status === TvVerificationBadge::STATUS_PENDING && self::isEligible($order)) {
                return self::markVerified($existing, null, 'auto_eligible');
            }

            return $existing;
        }

        if ($order->order_type === TvVerificationBadge::TYPE_OWNER
            && $order->customer_id
            && self::customerHasActiveOwnerBadge((int) $order->customer_id)) {
            return null;
        }

        if (! self::isEligible($order)) {
            return null;
        }

        return self::issueForOrder($order, null, false);
    }

    public static function isEligible(TvOrder $order): bool
    {
        if ($order->status !== 'completed') {
            return false;
        }

        if (! in_array($order->payment_status, ['paid', 'waived'], true)) {
            return false;
        }

        if (TrustVerificationDocumentService::missingRequiredTypes($order) !== []) {
            return false;
        }

        if (! self::allRequiredChecksPassed($order)) {
            return false;
        }

        if (TrustVerificationReferenceService::orderHasReferenceCheck($order)) {
            $progress = TrustVerificationReferenceService::progressForOrder($order);
            if (($progress['status'] ?? '') !== 'complete') {
                return false;
            }
        }

        if (TrustVerificationPoliceVerificationService::orderRequiresPoliceVerification($order)) {
            $order->loadMissing('policeVerification');
            if ($order->policeVerification?->status !== 'completed') {
                return false;
            }
        }

        return in_array($order->order_type, [TvVerificationBadge::TYPE_OWNER, TvVerificationBadge::TYPE_TENANT], true);
    }

    /** @return list<string> */
    public static function eligibilityBlockers(TvOrder $order): array
    {
        $blockers = [];

        if ($order->status !== 'completed') {
            $blockers[] = 'Order must be completed.';
        }
        if (! in_array($order->payment_status, ['paid', 'waived'], true)) {
            $blockers[] = 'Payment must be paid or waived.';
        }
        if (TrustVerificationDocumentService::missingRequiredTypes($order) !== []) {
            $blockers[] = 'Required documents are incomplete.';
        }
        if (! self::allRequiredChecksPassed($order)) {
            $blockers[] = 'All verification checks must pass.';
        }
        if (TrustVerificationReferenceService::orderHasReferenceCheck($order)) {
            $progress = TrustVerificationReferenceService::progressForOrder($order);
            if (($progress['status'] ?? '') !== 'complete') {
                $blockers[] = 'Reference check must be fully verified.';
            }
        }
        if (TrustVerificationPoliceVerificationService::orderRequiresPoliceVerification($order)) {
            $order->loadMissing('policeVerification');
            if ($order->policeVerification?->status !== 'completed') {
                $blockers[] = 'Police verification must be completed.';
            }
        }

        return $blockers;
    }

    public static function customerHasActiveOwnerBadge(int $customerId): bool
    {
        if (! self::issuedBadgeTableReady()) {
            return false;
        }

        return self::activeVerifiedIssuedBadge($customerId, TvVerificationBadge::TYPE_OWNER) !== null;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public static function assertCanIssueOwnerBadge(TvOrder $order, bool $adminOverride = false): void
    {
        if ($order->order_type !== TvVerificationBadge::TYPE_OWNER) {
            throw new \InvalidArgumentException('Only owner verification orders can receive an SVO badge.');
        }

        if (TvVerificationBadge::where('order_id', $order->id)->exists()) {
            throw new \InvalidArgumentException('This order already has a verification badge.');
        }

        if ($order->customer_id && self::customerHasActiveOwnerBadge((int) $order->customer_id)) {
            throw new \InvalidArgumentException('Customer already has an active Sukoon Verified Owner badge.');
        }

        if (! $adminOverride && ! self::isEligible($order)) {
            throw new \InvalidArgumentException('Order does not meet owner badge eligibility requirements.');
        }
    }

    public static function issueOwnerBadgeForOrder(
        TvOrder $order,
        ?int $adminUserId = null,
        bool $forceVerified = false
    ): TvVerificationBadge {
        self::assertCanIssueOwnerBadge($order, $forceVerified);

        return self::issueForOrder($order, $adminUserId, false, $forceVerified);
    }

    public static function issueForOrder(
        TvOrder $order,
        ?int $adminUserId = null,
        bool $forcePending = false,
        bool $forceVerified = false
    ): TvVerificationBadge {
        $order->loadMissing(['package', 'checkItems']);

        $badgeType = $order->order_type === TvVerificationBadge::TYPE_OWNER
            ? TvVerificationBadge::TYPE_OWNER
            : TvVerificationBadge::TYPE_TENANT;

        if ($badgeType === TvVerificationBadge::TYPE_OWNER
            && $order->customer_id
            && self::customerHasActiveOwnerBadge((int) $order->customer_id)) {
            throw new \InvalidArgumentException('Customer already has an active Sukoon Verified Owner badge.');
        }

        if (TvVerificationBadge::where('order_id', $order->id)->exists()) {
            throw new \InvalidArgumentException('This order already has a verification badge.');
        }

        $eligible = self::isEligible($order);
        $status = ($forceVerified || (! $forcePending && $eligible))
            ? TvVerificationBadge::STATUS_VERIFIED
            : TvVerificationBadge::STATUS_PENDING;

        $now = now();

        $badge = TvVerificationBadge::create([
            'customer_id' => (int) $order->customer_id,
            'order_id' => (int) $order->id,
            'badge_type' => $badgeType,
            'badge_number' => self::nextBadgeNumber($badgeType),
            'status' => $status,
            'issued_at' => $status === TvVerificationBadge::STATUS_VERIFIED ? $now : null,
            'expires_at' => $status === TvVerificationBadge::STATUS_VERIFIED
                ? $now->copy()->addMonths(self::DEFAULT_VALIDITY_MONTHS)
                : null,
            'metadata' => [
                'order_number' => $order->order_number,
                'issued_by_admin_id' => $adminUserId,
                'auto' => $adminUserId === null,
            ],
        ]);

        TrustVerificationAuditLogService::log([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'admin_id' => $adminUserId,
            'action' => TrustVerificationAuditLogService::ACTION_BADGE_ISSUED,
            'description' => 'Verification badge '.$badge->badge_number.' issued ('.$status.')',
            'metadata' => [
                'badge_id' => $badge->id,
                'badge_number' => $badge->badge_number,
                'badge_type' => $badge->badge_type,
                'status' => $badge->status,
            ],
        ]);

        return $badge;
    }

    public static function markVerified(TvVerificationBadge $badge, ?int $adminUserId = null, string $reason = 'manual_verify'): TvVerificationBadge
    {
        $now = now();
        $badge->status = TvVerificationBadge::STATUS_VERIFIED;
        $badge->issued_at = $badge->issued_at ?? $now;
        $badge->expires_at = $badge->expires_at ?? $now->copy()->addMonths(self::DEFAULT_VALIDITY_MONTHS);
        $badge->revoked_at = null;
        $badge->revoke_reason = null;
        $meta = $badge->metadata ?? [];
        $meta['verified_reason'] = $reason;
        $badge->metadata = $meta;
        $badge->save();

        TrustVerificationAuditLogService::log([
            'order_id' => $badge->order_id,
            'customer_id' => $badge->customer_id,
            'admin_id' => $adminUserId,
            'action' => TrustVerificationAuditLogService::ACTION_BADGE_ISSUED,
            'description' => 'Verification badge '.$badge->badge_number.' marked verified',
            'metadata' => ['badge_id' => $badge->id, 'reason' => $reason],
        ]);

        return $badge->fresh();
    }

    public static function revoke(TvVerificationBadge $badge, string $reason, ?int $adminUserId = null): TvVerificationBadge
    {
        $badge->status = TvVerificationBadge::STATUS_REVOKED;
        $badge->revoked_at = now();
        $badge->revoke_reason = mb_substr(trim($reason), 0, 500);
        $badge->save();

        TrustVerificationAuditLogService::log([
            'order_id' => $badge->order_id,
            'customer_id' => $badge->customer_id,
            'admin_id' => $adminUserId,
            'action' => TrustVerificationAuditLogService::ACTION_BADGE_REVOKED,
            'description' => 'Verification badge '.$badge->badge_number.' revoked',
            'metadata' => [
                'badge_id' => $badge->id,
                'reason' => $badge->revoke_reason,
            ],
        ]);

        return $badge->fresh();
    }

    public static function renew(TvVerificationBadge $badge, ?int $adminUserId = null, ?int $months = null): TvVerificationBadge
    {
        $months = $months ?? self::DEFAULT_VALIDITY_MONTHS;
        $badge->status = TvVerificationBadge::STATUS_VERIFIED;
        $badge->revoked_at = null;
        $badge->revoke_reason = null;
        $badge->issued_at = $badge->issued_at ?? now();
        $badge->expires_at = now()->addMonths($months);
        $badge->save();

        TrustVerificationAuditLogService::log([
            'order_id' => $badge->order_id,
            'customer_id' => $badge->customer_id,
            'admin_id' => $adminUserId,
            'action' => TrustVerificationAuditLogService::ACTION_BADGE_RENEWED,
            'description' => 'Verification badge '.$badge->badge_number.' renewed',
            'metadata' => [
                'badge_id' => $badge->id,
                'expires_at' => $badge->expires_at?->toIso8601String(),
            ],
        ]);

        return $badge->fresh();
    }

    public static function syncExpiredStatuses(): int
    {
        return TvVerificationBadge::query()
            ->where('status', TvVerificationBadge::STATUS_VERIFIED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => TvVerificationBadge::STATUS_EXPIRED]);
    }

    public static function nextBadgeNumber(string $badgeType): string
    {
        $year = (int) date('Y');
        $prefix = $badgeType === TvVerificationBadge::TYPE_OWNER ? 'SVO' : 'SVT';

        return DB::transaction(function () use ($prefix, $year, $badgeType) {
            $pattern = $prefix.'-'.$year.'-%';
            $last = TvVerificationBadge::query()
                ->where('badge_type', $badgeType)
                ->where('badge_number', 'like', $pattern)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->value('badge_number');

            $seq = 1;
            if ($last && preg_match('/-(\d+)$/', $last, $m)) {
                $seq = (int) $m[1] + 1;
            }

            return sprintf('%s-%d-%06d', $prefix, $year, $seq);
        });
    }

    public static function formatForCustomer(?TvVerificationBadge $badge): ?array
    {
        if (! $badge) {
            return null;
        }

        self::syncExpiredStatuses();
        $badge = $badge->fresh();

        $displayVerified = $badge->isCurrentlyVerified();

        return [
            'id' => $badge->id,
            'badge_type' => $badge->badge_type,
            'badge_title' => $badge->displayTitle(),
            'badge_number' => $badge->badge_number,
            'status' => $displayVerified ? TvVerificationBadge::STATUS_VERIFIED : $badge->status,
            'is_verified' => $displayVerified,
            'is_public_safe' => $badge->badge_type === TvVerificationBadge::TYPE_OWNER,
            'issued_at' => optional($badge->issued_at)?->toIso8601String(),
            'expires_at' => optional($badge->expires_at)?->toIso8601String(),
            'revoked_at' => optional($badge->revoked_at)?->toIso8601String(),
            'order_id' => $badge->order_id,
            'can_download' => $displayVerified,
        ];
    }

    public static function formatForOrder(TvOrder $order): ?array
    {
        $badge = TvVerificationBadge::where('order_id', $order->id)->first();

        return self::formatForCustomer($badge);
    }

    /** @return list<array<string, mixed>> */
    public static function activeBadgesForCustomer(int $customerId): array
    {
        self::syncExpiredStatuses();

        return TvVerificationBadge::query()
            ->where('customer_id', $customerId)
            ->whereIn('status', [TvVerificationBadge::STATUS_VERIFIED, TvVerificationBadge::STATUS_PENDING])
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (TvVerificationBadge $b) => self::formatForCustomer($b))
            ->filter(fn ($row) => $row && ($row['is_verified'] || $row['status'] === TvVerificationBadge::STATUS_PENDING))
            ->values()
            ->all();
    }

    public static function publicOwnerBadgeForCustomer(int $customerId): ?array
    {
        if (! self::hasActiveOwnerCardBadge($customerId)) {
            return null;
        }

        $badge = self::activeVerifiedIssuedBadge($customerId, TvVerificationBadge::TYPE_OWNER);

        return [
            'title' => $badge->displayTitle(),
            'badge_number' => $badge->badge_number,
            'issued_at' => optional($badge->issued_at)?->toIso8601String(),
        ];
    }

    /**
     * Explicit flags for property listing cards (owner priority, no catalog-badge guessing).
     *
     * @return array{owner_trust_badge: bool, tenant_trust_badge: bool}
     */
    public static function cardTrustBadgeFlags(int $customerId): array
    {
        if (! TrustVerificationTrustBadgeService::isPublicTrustEnabled()) {
            return ['owner_trust_badge' => false, 'tenant_trust_badge' => false];
        }

        $score = \App\Plugins\TrustVerification\Models\TvTrustScore::where('customer_id', $customerId)->first();
        if ($score && ! $score->public_visible) {
            return ['owner_trust_badge' => false, 'tenant_trust_badge' => false];
        }

        $owner = self::hasActiveOwnerCardBadge($customerId);

        return [
            'owner_trust_badge' => $owner,
            'tenant_trust_badge' => $owner ? false : self::hasActiveTenantTrustBadge($customerId),
        ];
    }

    public static function hasActiveOwnerCardBadge(int $customerId): bool
    {
        if (! TrustVerificationTrustBadgeService::isPublicTrustEnabled()) {
            return false;
        }

        if (self::issuedBadgeTableReady()) {
            return self::activeVerifiedIssuedBadge($customerId, TvVerificationBadge::TYPE_OWNER) !== null;
        }

        return self::hasCompletedOwnerVerification($customerId)
            && self::customerHasCatalogSlug($customerId, ['trusted_owner', 'owner_verified']);
    }

    /** Tenant trust for profile / My Orders — not used on public property cards. */
    public static function hasActiveTenantTrustBadge(int $customerId): bool
    {
        if (! TrustVerificationTrustBadgeService::isPublicTrustEnabled()) {
            return false;
        }

        if (! self::hasCompletedTenantVerification($customerId)) {
            return false;
        }

        $reliability = \App\Plugins\TrustVerification\Models\TvTenantReliability::where('customer_id', $customerId)->first();
        if (! $reliability) {
            return false;
        }

        if (! TrustVerificationTenantReliabilityService::isPublicEnabled() || ! $reliability->public_visible) {
            return false;
        }

        if (! in_array($reliability->reliability_level, [
            TrustVerificationTenantReliabilityService::LEVEL_TRUSTED,
            TrustVerificationTenantReliabilityService::LEVEL_PREMIUM,
        ], true)) {
            return false;
        }

        if (self::issuedBadgeTableReady()) {
            return self::activeVerifiedIssuedBadge($customerId, TvVerificationBadge::TYPE_TENANT) !== null;
        }

        // Do not treat tenant_verified alone as a listing pill (was causing all cards to show tenant).
        return self::customerHasCatalogSlug($customerId, ['trusted_tenant']);
    }

    protected static function issuedBadgeTableReady(): bool
    {
        return class_exists(TvVerificationBadge::class)
            && \Illuminate\Support\Facades\Schema::hasTable('tv_verification_badges');
    }

    protected static function hasCompletedOwnerVerification(int $customerId): bool
    {
        return TvOrder::query()
            ->where('customer_id', $customerId)
            ->where('order_type', 'owner')
            ->where('status', 'completed')
            ->whereIn('payment_status', ['paid', 'waived'])
            ->exists();
    }

    protected static function hasCompletedTenantVerification(int $customerId): bool
    {
        return TvOrder::query()
            ->where('customer_id', $customerId)
            ->where('order_type', 'tenant')
            ->where('status', 'completed')
            ->whereIn('payment_status', ['paid', 'waived'])
            ->exists();
    }

    /** @param  list<string>  $slugs */
    protected static function customerHasCatalogSlug(int $customerId, array $slugs): bool
    {
        return \App\Plugins\TrustVerification\Models\TvCustomerBadge::query()
            ->where('customer_id', $customerId)
            ->whereHas('badge', fn ($q) => $q->whereIn('slug', $slugs)->where('active', true))
            ->exists();
    }

    protected static function activeVerifiedIssuedBadge(int $customerId, string $badgeType): ?TvVerificationBadge
    {
        if (! self::issuedBadgeTableReady()) {
            return null;
        }

        self::syncExpiredStatuses();

        $badge = TvVerificationBadge::query()
            ->where('customer_id', $customerId)
            ->where('badge_type', $badgeType)
            ->where('status', TvVerificationBadge::STATUS_VERIFIED)
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->first();

        if (! $badge || ! $badge->isCurrentlyVerified()) {
            return null;
        }

        return $badge;
    }

    public static function logDownloaded(Request $request, TvVerificationBadge $badge, int $customerId): void
    {
        $order = $badge->order ?? TvOrder::find($badge->order_id);
        if (! $order) {
            return;
        }

        TrustVerificationAuditLogService::logCustomer(
            $request,
            $order,
            TrustVerificationAuditLogService::ACTION_BADGE_DOWNLOADED,
            'Customer downloaded verification badge '.$badge->badge_number,
            ['badge_id' => $badge->id],
            $customerId
        );
    }

    public static function certificateDownloadResponse(TvVerificationBadge $badge, bool $inline = false)
    {
        $badge->loadMissing('order');
        $orderNumber = $badge->order?->order_number ?? ('#'.$badge->order_id);
        $html = View::make('trust-verification::badge.certificate', [
            'badge' => $badge,
            'orderNumber' => $orderNumber,
            'issuedLabel' => $badge->issued_at?->format('d M Y') ?? '—',
            'expiresLabel' => $badge->expires_at?->format('d M Y') ?? '—',
        ])->render();

        $filename = $badge->badge_number.'-certificate.pdf';

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.$filename.'"',
            ]);
        }

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.str_replace('.pdf', '.html', $filename).'"',
        ]);
    }

    protected static function allRequiredChecksPassed(TvOrder $order): bool
    {
        $order->loadMissing('checkItems');

        foreach ($order->checkItems as $item) {
            if ($item->status === 'na') {
                continue;
            }
            if ($item->status !== 'pass') {
                return false;
            }
        }

        return true;
    }
}

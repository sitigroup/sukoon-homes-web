<?php

namespace App\Plugins\OwnerDashboard\Services;

use App\Models\Customer;
use App\Plugins\OwnerDashboard\Models\OwnerTenancy;
use App\Plugins\OwnerDashboard\Models\OwnerTenancyAuditLog;
use App\Plugins\OwnerDashboard\Services\OwnerNotificationService;
use App\Plugins\OwnerDashboard\Services\PendingOwnerLinkService;
use App\Plugins\RentalAgreement\Models\RentalAgreement;

/**
 * Single source of truth for owner_tenancy lifecycle transitions and owner_mode.
 *
 * All observers funnel into this service; observers stay thin. The service is the
 * only place that writes owner_tenancies.status / customers.owner_mode, so audit
 * logging and owner_mode recomputation stay consistent and loop-free.
 */
class OwnerTenancyStatusService
{
    /**
     * Activate a pending tenancy when ALL conditions are met:
     *   1. agreement linked
     *   2. agreement.status IN (signed_copy_uploaded, completed)
     *   3. a completed move_in checklist exists for that agreement
     */

    /**
     * Activate a KYC-only tenancy (no rental agreement, move-in optional).
     */
    public function tryActivateKycOnly(OwnerTenancy $tenancy): bool
    {
        if ($tenancy->status !== OwnerTenancy::STATUS_PENDING) {
            return false;
        }

        if (! $this->isKycOnlyTenancy($tenancy)) {
            return false;
        }

        if ($tenancy->rental_agreement_id) {
            return false;
        }

        $this->transition($tenancy, OwnerTenancy::STATUS_ACTIVE);

        return true;
    }

    private function isKycOnlyTenancy(OwnerTenancy $tenancy): bool
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('owner_tenancies', 'flow_type')) {
            return false;
        }

        return ($tenancy->flow_type ?? 'kyc_agreement') === 'kyc_only';
    }

    public function tryActivate(OwnerTenancy $tenancy): bool
    {
        if ($tenancy->status !== OwnerTenancy::STATUS_PENDING) {
            return false;
        }
        if (! $tenancy->rental_agreement_id) {
            return false;
        }

        $agreement = $tenancy->agreement;
        if (! $agreement || ! $this->agreementIsSigned($agreement)) {
            return false;
        }
        if (! $this->moveInComplete($tenancy->rental_agreement_id)) {
            return false;
        }

        $this->transition($tenancy, OwnerTenancy::STATUS_ACTIVE);

        return true;
    }

    /** RentalAgreement saved → link pending owner+property tenancies, then activate / cancel. */
    public function handleAgreementChange(RentalAgreement $agreement): void
    {
        if ($agreement->property_id) {
            $this->linkAgreementToPendingTenancies($agreement);
        }

        $tenancies = OwnerTenancy::where('rental_agreement_id', $agreement->id)->get();

        foreach ($tenancies as $tenancy) {
            if ($tenancy->status === OwnerTenancy::STATUS_PENDING) {
                if ($agreement->status === RentalAgreement::STATUS_CANCELLED) {
                    $this->transition($tenancy, OwnerTenancy::STATUS_CANCELLED);
                } else {
                    $this->tryActivate($tenancy);
                }
            }
        }
    }

    /** MoveInChecklist saved (type move_in) → try to activate pending tenancies. */
    public function handleMoveInChange(int $agreementId): void
    {
        $tenancies = OwnerTenancy::where('rental_agreement_id', $agreementId)
            ->where('status', OwnerTenancy::STATUS_PENDING)
            ->get();

        foreach ($tenancies as $tenancy) {
            $this->tryActivate($tenancy);
        }
    }

    /**
     * RenewalIntention saved → soft notice / revert.
     *   wants_to_vacate            → notice   (only if no active move-out process)
     *   wants_renewal / undecided  → notice reverts to active (if no move-out process)
     */
    public function handleRenewalIntention(int $agreementId, string $intention): void
    {
        $tenancies = OwnerTenancy::where('rental_agreement_id', $agreementId)
            ->whereIn('status', [OwnerTenancy::STATUS_ACTIVE, OwnerTenancy::STATUS_NOTICE])
            ->get();

        if ($tenancies->isEmpty()) {
            return;
        }

        $hasMoveOut = $this->hasActiveMoveOut($agreementId);

        foreach ($tenancies as $tenancy) {
            if ($intention === 'wants_to_vacate') {
                if (! $hasMoveOut && $tenancy->status === OwnerTenancy::STATUS_ACTIVE) {
                    $this->transition($tenancy, OwnerTenancy::STATUS_NOTICE);
                }
            } else {
                // wants_renewal / undecided → revert soft notice
                if (! $hasMoveOut && $tenancy->status === OwnerTenancy::STATUS_NOTICE) {
                    $this->transition($tenancy, OwnerTenancy::STATUS_ACTIVE);
                }
            }
        }
    }

    /** MoveOutProcess saved → move_out / completed / revert on cancel. */
    public function handleMoveOutChange(int $agreementId, string $processStatus): void
    {
        $tenancies = OwnerTenancy::where('rental_agreement_id', $agreementId)->get();

        foreach ($tenancies as $tenancy) {
            if (in_array($tenancy->status, [OwnerTenancy::STATUS_COMPLETED, OwnerTenancy::STATUS_CANCELLED], true)
                && $processStatus !== 'cancelled') {
                continue; // terminal state, leave alone
            }

            if ($processStatus === 'completed') {
                $this->transition($tenancy, OwnerTenancy::STATUS_COMPLETED);
            } elseif ($processStatus === 'cancelled') {
                // move-out aborted → tenant stays, reactivate
                if (in_array($tenancy->status, [OwnerTenancy::STATUS_MOVE_OUT, OwnerTenancy::STATUS_NOTICE], true)) {
                    $this->transition($tenancy, OwnerTenancy::STATUS_ACTIVE);
                }
            } else {
                if ($tenancy->status !== OwnerTenancy::STATUS_MOVE_OUT) {
                    $this->transition($tenancy, OwnerTenancy::STATUS_MOVE_OUT);
                }
            }
        }
    }

    /**
     * Recompute customers.owner_mode for one customer.
     *
     * owner_mode = owner has >=1 tenancy in a visible status (active|notice|move_out).
     * Freeze (dashboard_enabled=false) and demo handling live at the /me + dashboard
     * API layer; this flag only drives whether the nav tab appears.
     */
    public function recomputeOwnerMode(?int $customerId): void
    {
        if (! $customerId) {
            return;
        }

        $hasVisible = OwnerTenancy::where('owner_customer_id', $customerId)
            ->whereIn('status', OwnerTenancy::VISIBLE_STATUSES)
            ->exists();

        // Mass update avoids firing Customer model events (no observer loops).
        Customer::where('id', $customerId)->update(['owner_mode' => $hasVisible]);
    }

    // ── Admin actions (manual, attributed) ─────────────────────────────

    /** Admin manual status override → audited as actor=admin. */
    public function adminSetStatus(OwnerTenancy $tenancy, string $newStatus, ?int $adminId): bool
    {
        $allowed = [
            OwnerTenancy::STATUS_PENDING,
            OwnerTenancy::STATUS_ACTIVE,
            OwnerTenancy::STATUS_NOTICE,
            OwnerTenancy::STATUS_MOVE_OUT,
            OwnerTenancy::STATUS_COMPLETED,
            OwnerTenancy::STATUS_CANCELLED,
        ];
        if (! in_array($newStatus, $allowed, true)) {
            return false;
        }

        $this->transition($tenancy, $newStatus, OwnerTenancyAuditLog::ACTOR_ADMIN, $adminId);

        return true;
    }

    /** Admin freeze/unfreeze (or demo) toggle for the dashboard. */
    public function adminToggleDashboard(OwnerTenancy $tenancy, bool $enabled, ?int $adminId): void
    {
        if ((bool) $tenancy->dashboard_enabled === $enabled) {
            return;
        }

        $old = (bool) $tenancy->dashboard_enabled;
        $tenancy->dashboard_enabled = $enabled;
        $tenancy->saveQuietly();

        $this->logAudit(
            $tenancy,
            OwnerTenancyAuditLog::ACTION_DASHBOARD_TOGGLED,
            ['dashboard_enabled' => $old],
            ['dashboard_enabled' => $enabled],
            OwnerTenancyAuditLog::ACTOR_ADMIN,
            $adminId
        );

        $this->recomputeOwnerMode($tenancy->owner_customer_id);
    }

    // ── Internals ───────────────────────────────────────────────────────

    private function transition(
        OwnerTenancy $tenancy,
        string $newStatus,
        string $actorType = OwnerTenancyAuditLog::ACTOR_SYSTEM,
        ?int $actorId = null
    ): void {
        if ($tenancy->status === $newStatus) {
            return; // no real change → no write, no audit
        }

        $old = $tenancy->status;
        $tenancy->status = $newStatus;

        if ($newStatus === OwnerTenancy::STATUS_ACTIVE) {
            if (! $tenancy->activated_at) {
                $tenancy->activated_at = now();
            }
            $tenancy->ended_at = null; // reactivation (e.g. move-out cancelled)
        }

        if (in_array($newStatus, [OwnerTenancy::STATUS_COMPLETED, OwnerTenancy::STATUS_CANCELLED], true)) {
            $tenancy->ended_at = now();
        }

        // saveQuietly: no OwnerTenancy model events (defensive against future observers)
        $tenancy->saveQuietly();

        $this->logAudit(
            $tenancy,
            OwnerTenancyAuditLog::ACTION_STATUS_CHANGED,
            ['status' => $old],
            ['status' => $newStatus],
            $actorType,
            $actorId
        );

        $this->recomputeOwnerMode($tenancy->owner_customer_id);

        if ($newStatus === OwnerTenancy::STATUS_ACTIVE) {
            try {
                app(OwnerNotificationService::class)->dashboardActivated($tenancy->loadMissing('property'));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[OwnerDashboard] activation notify failed: ' . $e->getMessage());
            }
        }
    }

    public function logAudit(
        OwnerTenancy $tenancy,
        string $action,
        ?array $old,
        ?array $new,
        string $actorType = OwnerTenancyAuditLog::ACTOR_SYSTEM,
        ?int $actorId = null
    ): void {
        OwnerTenancyAuditLog::create([
            'owner_tenancy_id' => $tenancy->id,
            'actor_type'       => $actorType,
            'actor_id'         => $actorId,
            'action'           => $action,
            'old_values'       => $old,
            'new_values'       => $new,
            'created_at'       => now(),
        ]);
    }

    private function agreementIsSigned(RentalAgreement $agreement): bool
    {
        return in_array($agreement->status, [
            RentalAgreement::STATUS_SIGNED_COPY_UPLOADED,
            RentalAgreement::STATUS_COMPLETED,
        ], true);
    }

    private function moveInComplete(int $agreementId): bool
    {
        $model = \App\Plugins\MoveIn\Models\MoveInChecklist::class;

        return $model::where('agreement_id', $agreementId)
            ->where('type', $model::TYPE_MOVE_IN)
            ->where('status', $model::STATUS_COMPLETED)
            ->exists();
    }

    private function hasActiveMoveOut(int $agreementId): bool
    {
        $model = \App\Plugins\MoveOut\Models\MoveOutProcess::class;

        return $model::where('agreement_id', $agreementId)
            ->where('status', '!=', $model::STATUS_CANCELLED)
            ->exists();
    }

    /**
     * When a signed agreement exists for a property whose owner pointer matches a
     * pending owner+property tenancy, link the agreement (and tenant if known).
     */
    private function linkAgreementToPendingTenancies(RentalAgreement $agreement): void
    {
        if (! $agreement->property_id || ! $this->agreementIsSigned($agreement)) {
            return;
        }

        $pending = OwnerTenancy::query()
            ->where('property_id', $agreement->property_id)
            ->where('status', OwnerTenancy::STATUS_PENDING)
            ->whereNull('rental_agreement_id')
            ->get();

        if ($pending->isEmpty()) {
            return;
        }

        $propertyOwnerId = \App\Models\Property::where('id', $agreement->property_id)
            ->value('owner_customer_id');

        $linker = app(PendingOwnerLinkService::class);

        foreach ($pending as $tenancy) {
            if ($tenancy->owner_customer_id) {
                if ((int) $tenancy->owner_customer_id !== (int) $propertyOwnerId) {
                    continue;
                }
            } elseif (! $linker->phonesMatch($agreement->owner_phone, $tenancy->owner_phone)) {
                continue;
            }

            $updates = ['rental_agreement_id' => $agreement->id];
            if (! $tenancy->tenant_customer_id && $agreement->customer_id) {
                $updates['tenant_customer_id'] = $agreement->customer_id;
            }

            $tenancy->fill($updates);
            $tenancy->saveQuietly();
            $this->tryActivate($tenancy->fresh());
        }
    }
}

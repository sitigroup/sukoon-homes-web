<?php

namespace App\Plugins\OwnerDashboard\Http\Controllers\Admin;

use App\Models\Customer;
use App\Models\Property;
use App\Plugins\OwnerDashboard\Models\OwnerTenancy;
use App\Plugins\OwnerDashboard\Models\OwnerTenancyAuditLog;
use App\Plugins\OwnerDashboard\Services\OwnerTenancyStatusService;
use App\Plugins\OwnerDashboard\Services\PendingOwnerLinkService;
use App\Plugins\RentalAgreement\Models\RentalAgreement;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class OwnerTenancyAdminController extends Controller
{
    public function __construct(
        private readonly OwnerTenancyStatusService $service,
        private readonly PendingOwnerLinkService $ownerLink,
    ) {}

    public function index()
    {
        $tenancies = OwnerTenancy::with(['owner', 'tenant', 'property', 'agreement'])
            ->orderByDesc('id')
            ->get();

        $this->decorateConflicts($tenancies);

        $conflicting     = $tenancies->filter(fn (OwnerTenancy $t) => ! empty($t->conflict_reasons));
        $conflictSummary = [
            'count' => $conflicting->count(),
            'ids'   => $conflicting->pluck('id')->all(),
        ];

        $stats = [
            'total'         => $tenancies->count(),
            'active'        => $tenancies->where('status', OwnerTenancy::STATUS_ACTIVE)->count(),
            'pending'       => $tenancies->where('status', OwnerTenancy::STATUS_PENDING)->count(),
            'pending_owner' => $tenancies->filter(fn (OwnerTenancy $t) => $t->isPendingOwner())->count(),
            'notice'        => $tenancies->whereIn('status', [OwnerTenancy::STATUS_NOTICE, OwnerTenancy::STATUS_MOVE_OUT])->count(),
            'completed'     => $tenancies->where('status', OwnerTenancy::STATUS_COMPLETED)->count(),
        ];

        $properties = Property::orderBy('title')->get(['id', 'title', 'owner_customer_id']);
        $customers  = Customer::orderBy('name')->get(['id', 'name', 'mobile', 'email']);

        return view('owner-dashboard::admin.tenancies.index', compact('tenancies', 'stats', 'properties', 'customers', 'conflictSummary'));
    }

    public function create()
    {
        return view('owner-dashboard::admin.tenancies.form', [
            'tenancy'    => new OwnerTenancy(),
            'customers'  => Customer::orderBy('name')->get(['id', 'name', 'mobile']),
            'properties' => Property::orderBy('title')->get(['id', 'title']),
            'agreements' => RentalAgreement::orderByDesc('id')->get(['id', 'agreement_number', 'tenant_name', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateTenancy($request);

        $tenancy = OwnerTenancy::create([
            'owner_customer_id'   => $data['owner_customer_id'],
            'tenant_customer_id'  => $data['tenant_customer_id'] ?? null,
            'property_id'         => $data['property_id'],
            'rental_agreement_id' => $data['rental_agreement_id'] ?? null,
            'status'              => OwnerTenancy::STATUS_PENDING,
            'dashboard_enabled'   => $request->boolean('dashboard_enabled', true),
            'owner_maintenance_mode' => in_array($request->input('owner_maintenance_mode'), ['involved', 'hands_off'], true) ? $request->input('owner_maintenance_mode') : 'involved',
            'created_by_admin_id' => auth()->id(),
        ]);

        $this->service->logAudit(
            $tenancy,
            OwnerTenancyAuditLog::ACTION_CREATED,
            null,
            $tenancy->only(['owner_customer_id', 'tenant_customer_id', 'property_id', 'rental_agreement_id', 'status']),
            OwnerTenancyAuditLog::ACTOR_ADMIN,
            auth()->id()
        );

        $this->assignOwnerToProperty($data['property_id'], $data['owner_customer_id']);
        $this->service->tryActivate($tenancy->fresh());

        return redirect()->route('admin.owner-tenancies.index')
            ->with('success', __('Owner tenancy created.'));
    }

    public function edit(OwnerTenancy $owner_tenancy)
    {
        return view('owner-dashboard::admin.tenancies.form', [
            'tenancy'    => $owner_tenancy,
            'customers'  => Customer::orderBy('name')->get(['id', 'name', 'mobile']),
            'properties' => Property::orderBy('title')->get(['id', 'title']),
            'agreements' => RentalAgreement::orderByDesc('id')->get(['id', 'agreement_number', 'tenant_name', 'status']),
        ]);
    }

    public function update(Request $request, OwnerTenancy $owner_tenancy)
    {
        $data = $this->validateTenancy($request);

        $owner_tenancy->update([
            'owner_customer_id'   => $data['owner_customer_id'],
            'tenant_customer_id'  => $data['tenant_customer_id'] ?? null,
            'property_id'         => $data['property_id'],
            'rental_agreement_id' => $data['rental_agreement_id'] ?? null,
        ]);

        $this->assignOwnerToProperty($data['property_id'], $data['owner_customer_id']);
        $this->service->adminToggleDashboard($owner_tenancy->fresh(), $request->boolean('dashboard_enabled'), auth()->id());
        $this->service->recomputeOwnerMode($data['owner_customer_id']);

        if ($owner_tenancy->status === OwnerTenancy::STATUS_PENDING) {
            $this->service->tryActivate($owner_tenancy->fresh());
        }

        return redirect()->route('admin.owner-tenancies.index')
            ->with('success', __('Owner tenancy updated.'));
    }

    public function linkOwner(Request $request, OwnerTenancy $owner_tenancy)
    {
        $request->validate([
            'owner_customer_id' => 'required|integer|exists:customers,id',
        ]);

        if (! $owner_tenancy->isPendingOwner()) {
            return redirect()->route('admin.owner-tenancies.index')
                ->with('error', __('This tenancy already has a registered owner.'));
        }

        $ok = $this->ownerLink->linkOwnerToTenancy(
            $owner_tenancy,
            (int) $request->owner_customer_id,
            auth()->id(),
            OwnerTenancyAuditLog::ACTOR_ADMIN
        );

        return redirect()->route('admin.owner-tenancies.index')
            ->with($ok ? 'success' : 'error', $ok
                ? __('Owner linked to tenancy.')
                : __('Could not link owner.'));
    }

    public function editPayments(OwnerTenancy $owner_tenancy)
    {
        $payments = null;
        if (class_exists(\App\Plugins\MoveIn\Services\PreMoveInPaymentService::class)) {
            $payments = app(\App\Plugins\MoveIn\Services\PreMoveInPaymentService::class)
                ->serialize($owner_tenancy->fresh(['agreement']));
        }

        return view('owner-dashboard::admin.tenancies.payments-override', [
            'tenancy'  => $owner_tenancy->load('property'),
            'payments' => $payments,
        ]);
    }

    public function changeStatus(Request $request, OwnerTenancy $owner_tenancy)
    {
        $request->validate(['status' => 'required|string']);

        $ok = $this->service->adminSetStatus($owner_tenancy, $request->input('status'), auth()->id());

        return redirect()->route('admin.owner-tenancies.index')
            ->with($ok ? 'success' : 'error', $ok ? __('Status updated.') : __('Invalid status.'));
    }

    public function toggleDashboard(OwnerTenancy $owner_tenancy)
    {
        $this->service->adminToggleDashboard($owner_tenancy, ! $owner_tenancy->dashboard_enabled, auth()->id());

        return redirect()->route('admin.owner-tenancies.index')
            ->with('success', __('Dashboard visibility updated.'));
    }

    public function sendRentReminder(Request $request, OwnerTenancy $owner_tenancy)
    {
        $validated = $request->validate([
            'due_date' => 'required|date',
            'rent_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            $tenancy = $owner_tenancy->loadMissing(['tenant', 'property', 'agreement']);
            $tenantPhone = $this->resolveTenantPhone($tenancy);
            if ($tenantPhone === '') {
                return redirect()->route('admin.owner-tenancies.index')
                    ->with('error', __('Tenant phone is missing. Rent reminder not sent.'));
            }

            $rentAmount = (string) ($validated['rent_amount'] ?? $tenancy->monthly_rent ?? 0);
            $propertyLabel = (string) ($tenancy->property?->title ?? ('#' . $tenancy->property_id));
            $dueDate = (string) $validated['due_date'];

            $result = \Whatsapp::notify('rent_reminder', [
                'phone' => $tenantPhone,
                'customer_type' => 'tenant',
                'customer_id' => $tenancy->tenant_customer_id,
            ], [
                $rentAmount,
                $propertyLabel,
                $dueDate,
            ]);

            return redirect()->route('admin.owner-tenancies.index')
                ->with('success', __('Rent reminder processed.'))
                ->with('whatsapp_feedback', $result);
        } catch (\Throwable $e) {
            Log::warning('Manual rent reminder failed', [
                'owner_tenancy_id' => $owner_tenancy->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('admin.owner-tenancies.index')
                ->with('error', __('Unable to send rent reminder right now.'));
        }
    }

    public function assignOwner(Request $request)
    {
        $request->validate([
            'property_id'       => 'required|integer|exists:propertys,id',
            'owner_customer_id' => 'required|integer|exists:customers,id',
        ]);

        $this->assignOwnerToProperty((int) $request->property_id, (int) $request->owner_customer_id);

        return redirect()->route('admin.owner-tenancies.index')
            ->with('success', __('Owner assigned to property.'));
    }

    private function validateTenancy(Request $request): array
    {
        return $request->validate([
            'owner_customer_id'   => 'required|integer|exists:customers,id',
            'tenant_customer_id'  => 'nullable|integer|exists:customers,id',
            'property_id'         => 'required|integer|exists:propertys,id',
            'rental_agreement_id' => 'nullable|integer|exists:rental_agreements,id',
        ]);
    }

    private function assignOwnerToProperty(int $propertyId, ?int $ownerCustomerId): void
    {
        if ($propertyId <= 0 || ! $ownerCustomerId) {
            return;
        }

        Property::where('id', $propertyId)->update(['owner_customer_id' => $ownerCustomerId]);
        Log::info("[OwnerDashboard] property#{$propertyId} owner_customer_id set to {$ownerCustomerId} by admin#" . auth()->id());
    }

    private function decorateConflicts($tenancies): void
    {
        $live = [OwnerTenancy::STATUS_ACTIVE, OwnerTenancy::STATUS_NOTICE, OwnerTenancy::STATUS_MOVE_OUT];

        $liveByProperty = $tenancies
            ->filter(fn (OwnerTenancy $t) => in_array($t->status, $live, true))
            ->groupBy('property_id');

        foreach ($tenancies as $t) {
            $reasons  = [];
            $isLive   = in_array($t->status, $live, true);
            $isClosed = in_array($t->status, [OwnerTenancy::STATUS_COMPLETED, OwnerTenancy::STATUS_CANCELLED], true);

            $t->setAttribute('pending_owner', $t->isPendingOwner());

            if ($t->isPendingOwner()) {
                if ($isLive) {
                    $reasons[] = __('Owner not registered yet — link when they sign up');
                }
            } elseif (! $isClosed) {
                $propOwner = $t->property?->owner_customer_id;

                if ($propOwner !== null && (int) $propOwner !== (int) $t->owner_customer_id) {
                    $reasons[] = __('Property owner pointer is #:id, not this owner', ['id' => $propOwner]);
                }
                if ($propOwner === null && $isLive) {
                    $reasons[] = __('Property has no owner pointer set');
                }
            }

            if ($isLive) {
                $others = ($liveByProperty[$t->property_id] ?? collect())
                    ->filter(fn (OwnerTenancy $o) => $o->id !== $t->id);

                if ($others->isNotEmpty()) {
                    $ids       = $others->pluck('id')->map(fn ($i) => '#' . $i)->implode(', ');
                    $diffOwner = $others->contains(function (OwnerTenancy $o) use ($t) {
                        if ($t->isPendingOwner() || $o->isPendingOwner()) {
                            return false;
                        }

                        return (int) $o->owner_customer_id !== (int) $t->owner_customer_id;
                    });

                    $reasons[] = $diffOwner
                        ? __('Another live tenancy with a different owner (:ids)', ['ids' => $ids])
                        : __('Another live tenancy on this property (:ids)', ['ids' => $ids]);
                }
            }

            $t->setAttribute('conflict_reasons', $reasons);
            $t->setAttribute('owner_conflict', ! empty($reasons) && ! $t->isPendingOwner());
        }
    }

    private function resolveTenantPhone(OwnerTenancy $tenancy): string
    {
        $phone = preg_replace('/\D+/', '', (string) ($tenancy->agreement?->tenant_phone ?? ''));
        if ($phone !== '') {
            return $phone;
        }

        $tenant = $tenancy->tenant;
        if (! $tenant && (int) ($tenancy->tenant_customer_id ?? 0) > 0) {
            $tenant = Customer::query()->find((int) $tenancy->tenant_customer_id);
        }

        return preg_replace('/\D+/', '', (string) ($tenant?->mobile ?? $tenant?->phone ?? ''));
    }
}

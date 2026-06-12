<?php

namespace App\Plugins\Maintenance\Services;

use App\Models\Usertokens;
use App\Plugins\AreaManagement\Services\AreaRoutingNotificationService;
use App\Plugins\AreaManagement\Services\AreaRoutingService;
use App\Plugins\Maintenance\Models\MaintenancePhoto;
use App\Plugins\Maintenance\Models\MaintenanceRequest;
use App\Plugins\Maintenance\Models\MaintenanceUpdate;
use App\Plugins\Maintenance\Services\MaintenanceUpdateActorService;
use App\Plugins\Maintenance\Models\MaintenanceVendor;
use App\Plugins\Maintenance\Services\MaintenanceResponsibilityNotificationService;
use App\Plugins\Maintenance\Services\MaintenanceResponsibilityService;
use App\Plugins\RentalAgreement\Models\RentalAgreement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class MaintenanceService
{
    private ?string $lastRoutingNote = null;

    public function consumeRoutingNote(): ?string
    {
        $note = $this->lastRoutingNote;
        $this->lastRoutingNote = null;

        return $note;
    }

    private function nextNumber(): string
    {
        $last = MaintenanceRequest::whereYear('created_at', now()->year)
            ->lockForUpdate()->orderByDesc('id')->value('request_number');
        $next = $last ? (int) substr($last, -6) + 1 : 1;

        return 'MR-' . now()->year . '-' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }

    public function create(array $data, ?int $customerId = null): MaintenanceRequest
    {
        return DB::transaction(function () use ($data, $customerId) {
            $data       = $this->enrichPropertyIdForCreate($data, $customerId);
            $propertyId = ! empty($data['property_id']) ? (int) $data['property_id'] : null;
            $sub = app(\App\Plugins\Maintenance\Services\MaintenanceSubcategoryService::class)->resolveForCreate(
                app(MaintenanceResponsibilityService::class)->normalizeCategory($data['category'] ?? 'other'),
                isset($data['subcategory_id']) ? (int) $data['subcategory_id'] : null,
                $data['subcategory_name'] ?? null
            );

            $request = MaintenanceRequest::create([
                'request_number' => $this->nextNumber(),
                'agreement_id'   => $data['agreement_id'] ?? null,
                'customer_id'    => $customerId ?? $data['customer_id'] ?? null,
                'property_id'    => $propertyId,
                'title'          => $data['title'],
                'description'    => $data['description'],
                'category'       => app(MaintenanceResponsibilityService::class)->normalizeCategory($data['category'] ?? 'other'),
                'subcategory_id'   => $sub['subcategory_id'],
                'subcategory_name' => $sub['subcategory_name'],
                'sub_area_id'      => ! empty($data['sub_area_id']) ? (int) $data['sub_area_id'] : null,
                'priority'       => $data['priority'] ?? 'normal',
                'status'         => MaintenanceRequest::STATUS_OPEN,
                'cost_bearer'    => 'pending',
                'cost_recovery'  => 'none',
            ]);

            if (! empty($data['sub_area_id']) && $propertyId) {
                $this->syncPropertySubAreaIfNeeded($propertyId, (int) $data['sub_area_id']);
            }

            $this->applyResponsibilityRouting($request, $propertyId);

            $this->addUpdate($request, 'open', __('Request created.'), null, MaintenanceUpdateActorService::TYPE_SYSTEM, null);

            return $request;
        });
    }

    /**
     * Resolve property_id from rental agreement or active owner tenancy (tenant API).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function enrichPropertyIdForCreate(array $data, ?int $customerId = null): array
    {
        try {
            if (! empty($data['property_id'])) {
                return $data;
            }

            $agreementId = $data['agreement_id'] ?? $data['rental_agreement_id'] ?? null;
            if ($agreementId) {
                $agreement = RentalAgreement::find($agreementId);
                if ($agreement?->property_id) {
                    $data['property_id']  = (int) $agreement->property_id;
                    $data['agreement_id'] = (int) $agreement->id;

                    return $data;
                }
            }

            if ($customerId && class_exists(\App\Plugins\OwnerDashboard\Models\OwnerTenancy::class)
                && Schema::hasTable('owner_tenancies')) {
                $tenancyClass = \App\Plugins\OwnerDashboard\Models\OwnerTenancy::class;
                $query        = $tenancyClass::query()
                    ->where('tenant_customer_id', $customerId)
                    ->whereIn('status', $tenancyClass::VISIBLE_STATUSES);

                if ($agreementId) {
                    $query->where('rental_agreement_id', $agreementId);
                }

                $tenancy = $query->orderByDesc('id')->first();

                if ($tenancy?->property_id) {
                    $data['property_id'] = (int) $tenancy->property_id;
                    if (empty($data['agreement_id']) && $tenancy->rental_agreement_id) {
                        $data['agreement_id'] = (int) $tenancy->rental_agreement_id;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Maintenance property_id resolution failed', [
                'customer_id' => $customerId,
                'error'       => $e->getMessage(),
            ]);
        }

        return $data;
    }

    /** Backfill legacy rows: set property_id and re-run area routing. */
    public function rerouteLegacyRequest(MaintenanceRequest $request): MaintenanceRequest
    {
        try {
            $data = $this->enrichPropertyIdForCreate([
                'agreement_id' => $request->agreement_id,
                'property_id'  => $request->property_id,
            ], $request->customer_id);

            $propertyId = ! empty($data['property_id']) ? (int) $data['property_id'] : null;
            if (! $propertyId) {
                return $request;
            }

            $updates = ['property_id' => $propertyId];
            if (empty($request->agreement_id) && ! empty($data['agreement_id'])) {
                $updates['agreement_id'] = (int) $data['agreement_id'];
            }
            $request->update($updates);
            $request = $request->fresh();

            $this->applyMaintenanceRouting($request, $propertyId);
        } catch (\Throwable $e) {
            Log::warning('Maintenance legacy reroute failed', [
                'request_id' => $request->id,
                'error'      => $e->getMessage(),
            ]);
        }

        return $request->fresh();
    }

    public function assignVendor(MaintenanceRequest $request, int $vendorId, ?string $note = null, ?int $adminId = null, ?string $actorType = null, ?int $actorId = null): MaintenanceRequest
    {
        $vendor = MaintenanceVendor::findOrFail($vendorId);

        $request->update([
            'vendor_id'   => $vendorId,
            'status'      => MaintenanceRequest::STATUS_ASSIGNED,
            'assigned_at' => now(),
        ]);

        $this->addUpdate($request, 'assigned', __('Vendor assigned: :name', ['name' => $vendor->name]) . ($note ? ' — ' . $note : ''), $adminId, $actorType ?? MaintenanceUpdateActorService::TYPE_ADMIN, $actorId ?? $adminId);
        app(MaintenanceResponsibilityNotificationService::class)->notifyStatusOrAssignment(
            $request,
            __('Vendor Assigned'),
            __('A vendor has been assigned to your maintenance request: :title', ['title' => $request->title]),
            'maintenance_vendor_assigned'
        );
        $this->notifyVendor($request, $vendor);

        try {
            if (class_exists(\App\Plugins\Whatsapp\Services\WhatsappPlatformEventService::class)) {
                app(\App\Plugins\Whatsapp\Services\WhatsappPlatformEventService::class)
                    ->notifyMaintenanceAssigned($request->fresh(['agreement', 'vendor']));
            }
        } catch (\Throwable $e) {
            Log::warning('WhatsApp maintenance_assigned hook failed', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
        }

        $vendor->increment('total_jobs');

        return $request->fresh();
    }

    public function updateStatus(MaintenanceRequest $request, string $status, ?string $note = null, ?int $adminId = null, ?string $actorType = null, ?int $actorId = null): MaintenanceRequest
    {
        $updates = ['status' => $status];

        if ($status === MaintenanceRequest::STATUS_RESOLVED) {
            $updates['resolved_at'] = now();
            if (\Illuminate\Support\Facades\Schema::hasColumn('maintenance_requests', 'tenant_confirmed')) {
                $updates['tenant_confirmed']    = null;
                $updates['tenant_confirmed_at'] = null;
            }
        }
        if ($status === MaintenanceRequest::STATUS_CLOSED) {
            $updates['closed_at'] = now();
            if (\Illuminate\Support\Facades\Schema::hasColumn('maintenance_requests', 'tenant_confirmed')) {
                $updates['tenant_confirmed']    = $updates['tenant_confirmed'] ?? true;
                $updates['tenant_confirmed_at'] = $updates['tenant_confirmed_at'] ?? now();
            }
        }

        $request->update($updates);
        $this->addUpdate($request, $status, $note ?? __('Status updated to :status', ['status' => $status]), $adminId, $actorType ?? MaintenanceUpdateActorService::TYPE_ADMIN, $actorId ?? $adminId);

        $notify = app(MaintenanceResponsibilityNotificationService::class);
        if ($status === MaintenanceRequest::STATUS_RESOLVED) {
            $notify->notifyTenantConfirmationNeeded($request);
        } else {
            $label = ucwords(str_replace('_', ' ', $status));
            $notify->notifyStatusOrAssignment(
                $request,
                __('Maintenance update'),
                __('Request :num is now :status.', ['num' => $request->request_number, 'status' => $label]),
                'maintenance_status_updated'
            );
        }

        return $request->fresh();
    }

    public function setCost(MaintenanceRequest $request, array $data, ?int $adminId = null): MaintenanceRequest
    {
        $request->update([
            'estimated_cost' => $data['estimated_cost'] ?? $request->estimated_cost,
            'actual_cost'    => $data['actual_cost']    ?? $request->actual_cost,
            'cost_bearer'    => $data['cost_bearer'],
            'cost_recovery'  => $data['cost_recovery'],
            'cost_settled'   => $data['cost_settled'] ?? false,
        ]);

        $costNote = 'Cost set: Rs. ' . number_format($data['actual_cost'] ?? 0) . ' — bearer: ' . $data['cost_bearer'] . ' — recovery: ' . $data['cost_recovery'];
        $this->addUpdate($request, null, $costNote, $adminId, MaintenanceUpdateActorService::TYPE_ADMIN, $adminId);

        if ($data['cost_bearer'] === 'tenant') {
            app(MaintenanceResponsibilityNotificationService::class)->notifyStatusOrAssignment(
                $request,
                __('Maintenance Cost') . ' — ' . $request->request_number,
                __('Maintenance cost of Rs. :amount has been assigned to you.', ['amount' => number_format($data['actual_cost'] ?? 0)]),
                'maintenance_cost_assigned'
            );
        }

        return $request->fresh();
    }

    public function uploadPhoto(MaintenanceRequest $request, UploadedFile $file, string $type, ?string $caption, ?int $adminId): string
    {
        $ext  = $file->getClientOriginalExtension();
        $path = 'maintenance/' . $request->id . '/' . $type . '-' . uniqid() . '.' . $ext;
        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        MaintenancePhoto::create([
            'request_id'  => $request->id,
            'photo_path'  => $path,
            'type'        => $type,
            'caption'     => $caption,
            'uploaded_by' => $adminId,
            'uploaded_at' => now(),
        ]);

        return $path;
    }


    public function rerouteToAreaAgent(MaintenanceRequest $request, ?int $propertyId = null): void
    {
        $this->applyMaintenanceRouting($request, $propertyId ?? ((int) ($request->property_id ?? 0) ?: null));
    }


    /**
     * When admin selects a sub-area, persist on the request and property location for routing.
     */
    private function syncPropertySubAreaIfNeeded(?int $propertyId, ?int $subAreaId): void
    {
        if ($propertyId <= 0 || $subAreaId <= 0 || ! Schema::hasTable('area_listing_property_locations')) {
            return;
        }

        try {
            if (! Schema::hasColumn('area_listing_property_locations', 'sub_area_id')) {
                return;
            }

            if (DB::table('area_listing_property_locations')->where('property_id', $propertyId)->exists()) {
                DB::table('area_listing_property_locations')
                    ->where('property_id', $propertyId)
                    ->update(['sub_area_id' => $subAreaId]);
            }
        } catch (\Throwable $e) {
            Log::warning('Maintenance sub_area sync failed', ['property_id' => $propertyId, 'error' => $e->getMessage()]);
        }
    }
    private function applyResponsibilityRouting(MaintenanceRequest $request, ?int $propertyId): void
    {
        try {
            if (! class_exists(MaintenanceResponsibilityService::class)) {
                $this->applyMaintenanceRouting($request, $propertyId);

                return;
            }

            $respSvc = app(MaintenanceResponsibilityService::class);
            $tenancy = $respSvc->findTenancyForRequest($request);
            $request  = $respSvc->applyRoutingToRequest($request, $tenancy);

            $routedTo = (string) ($request->routed_to ?? MaintenanceResponsibilityService::ROUTED_TO_AGENT);
            if ($routedTo === MaintenanceResponsibilityService::ROUTED_TO_AGENT) {
                $this->applyMaintenanceRouting($request, $propertyId);
            }
        } catch (\Throwable $e) {
            Log::warning('Maintenance responsibility routing failed', ['error' => $e->getMessage()]);
            try {
                $this->applyMaintenanceRouting($request, $propertyId);
            } catch (\Throwable $inner) {
                Log::warning('Maintenance area routing fallback failed', ['error' => $inner->getMessage()]);
            }
        }
    }

    private function applyMaintenanceRouting(MaintenanceRequest $request, ?int $propertyId): void
    {
        try {
            if (! $propertyId || ! class_exists(AreaRoutingService::class)) {
                $this->notifyAdminFallback($request);

                return;
            }

            $routing = app(AreaRoutingService::class);
            $parties = $routing->getResponsibleParties('maintenance', $propertyId);

            $routedAgentId = null;
            if (! $parties['fell_back_to_admin'] && $parties['handlers'] !== []) {
                $routedAgentId = (int) $parties['handlers'][0];
            }

            if ($routedAgentId && Schema::hasColumn('maintenance_requests', 'routed_agent_id')) {
                $request->update(['routed_agent_id' => $routedAgentId]);
            }

            if ($parties['fell_back_to_admin']) {
                $this->lastRoutingNote = __('No agent in area, routed to admin');
            }

            if (class_exists(MaintenanceResponsibilityNotificationService::class)) {
                app(MaintenanceResponsibilityNotificationService::class)->notifyCreated(
                    $request,
                    $propertyId,
                    __('New maintenance request'),
                    $request->request_number . ': ' . $request->title,
                    'maintenance_created'
                );
            } elseif (class_exists(AreaRoutingNotificationService::class)) {
                app(AreaRoutingNotificationService::class)->notifyForTask(
                    'maintenance',
                    $propertyId,
                    __('New maintenance request'),
                    $request->request_number . ': ' . $request->title,
                    'maintenance_created',
                    ['request_id' => $request->id, 'property_id' => $propertyId]
                );
            } else {
                $this->notifyAdminFallback($request);
            }
        } catch (\Throwable $e) {
            Log::warning('Maintenance routing failed', ['error' => $e->getMessage()]);
            $this->notifyAdminFallback($request);
        }
    }

    private function notifyAdminFallback(MaintenanceRequest $request): void
    {
        try {
            if (class_exists(AreaRoutingNotificationService::class)) {
                app(AreaRoutingNotificationService::class)->notifyForTask(
                    'maintenance',
                    $request->property_id ? (int) $request->property_id : null,
                    __('New maintenance request'),
                    $request->request_number . ': ' . $request->title,
                    'maintenance_created',
                    ['request_id' => $request->id]
                );
            }
        } catch (\Throwable $e) {
            Log::info('Maintenance request created: ' . $request->request_number . ' priority: ' . $request->priority);
        }
    }

    public function addUpdate(MaintenanceRequest $request, ?string $status, string $note, ?int $adminId): void
    {
        MaintenanceUpdate::create([
            'request_id' => $request->id,
            'admin_id'   => $adminId,
            'status'     => $status,
            'note'       => $note,
            'created_at' => now(),
        ]);
    }

    private function notifyVendor(MaintenanceRequest $request, MaintenanceVendor $vendor): void
    {
        try {
            Log::info('Maintenance vendor assigned', [
                'request_number' => $request->request_number,
                'vendor_id'      => $vendor->id,
                'vendor_phone'   => $vendor->phone ? substr($vendor->phone, 0, 4) . '****' : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('MaintenanceService: vendor notify failed', ['error' => $e->getMessage()]);
        }
    }

    private function notifyCustomer(MaintenanceRequest $request, string $title, string $body, string $type): void
    {
        if (! $request->customer_id || ! function_exists('send_push_notification')) {
            return;
        }
        try {
            $tokens = Usertokens::where('customer_id', $request->customer_id)->pluck('fcm_id')->filter()->values()->toArray();
            if (empty($tokens)) {
                return;
            }
            foreach (array_chunk($tokens, 1000) as $batch) {
                send_push_notification($batch, [
                    'title'        => $title,
                    'message'      => $body,
                    'type'         => $type,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'request_id'   => $request->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('MaintenanceService: push failed', ['error' => $e->getMessage()]);
        }
    }
}

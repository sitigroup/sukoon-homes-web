<?php

namespace App\Plugins\AreaManagement\Http\Controllers\Api;

use App\Plugins\AreaManagement\Services\AgentDashboardQueryService;
use App\Plugins\AreaManagement\Services\AreaRoutingService;
use App\Plugins\AreaManagement\Support\RequiresAgentCustomer;
use App\Plugins\Maintenance\Models\MaintenanceRequest;
use App\Plugins\Maintenance\Models\MaintenanceVendor;
use App\Plugins\Maintenance\Services\MaintenanceResponsibilityService;
use App\Plugins\Maintenance\Services\MaintenanceCategoryService;
use App\Plugins\Maintenance\Services\MaintenanceSubcategoryService;
use App\Plugins\Maintenance\Services\MaintenanceTenancyDisplayService;
use App\Plugins\Maintenance\Services\MaintenanceService;
use App\Plugins\Maintenance\Services\MaintenanceUpdateActorService;
use App\Plugins\Maintenance\Support\MaintenanceVendorWhatsApp;
use App\Plugins\Maintenance\Services\MaintenanceCoordinationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class AgentMaintenanceApiController extends Controller
{
    use RequiresAgentCustomer;

    public function __construct(
        private readonly MaintenanceService $service,
        private readonly AgentDashboardQueryService $dashboardQueries,
        private readonly MaintenanceResponsibilityService $responsibilityService,
        private readonly MaintenanceCategoryService $categoryService,
        private readonly MaintenanceSubcategoryService $subcategoryService,
    ) {}

    /** GET /api/agent/maintenance/{id} */
    public function show(int $id): JsonResponse
    {
        $request = $this->resolveAccessibleRequest($id);

        return response()->json(['data' => $this->format($request, true)]);
    }

    /** GET /api/agent/maintenance/{id}/vendors */
    public function vendors(int $id): JsonResponse
    {
        $request = $this->resolveAccessibleRequest($id);
        $bundle  = $this->vendorListsForRequest($request);

        return response()->json(['data' => $bundle]);
    }

    /** POST /api/agent/maintenance/{id}/status */
    public function updateStatus(Request $httpRequest, int $id): JsonResponse
    {
        $request = $this->resolveAccessibleRequest($id);
        $data    = $httpRequest->validate([
            'status' => 'required|in:open,assigned,in_progress,resolved,closed,cancelled',
            'note'   => 'nullable|string|max:500',
        ]);

        try {
            $updated = $this->service->updateStatus(
                $request,
                $data['status'],
                $data['note'] ?? null,
                $this->agentCustomerId()
            );

            $whatsappResult = null;
            if (($data['status'] ?? '') === 'resolved') {
                $whatsappResult = $this->notifyMaintenanceResolved($updated);
            }

            $payload = $this->format($updated, true);
            if (is_array($whatsappResult)) {
                $payload['whatsapp_result'] = $whatsappResult;
            }

            return response()->json([
                'message' => __('Status updated.'),
                'data'    => $payload,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Agent maintenance status update failed', [
                'request_id' => $id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json(['message' => __('Unable to update status.')], 500);
        }
    }

    /** POST /api/agent/maintenance/{id}/assign-vendor */
    public function assignVendor(Request $httpRequest, int $id): JsonResponse
    {
        $request = $this->resolveAccessibleRequest($id);
        $data    = $httpRequest->validate([
            'vendor_id' => 'required|integer|exists:maintenance_vendors,id',
            'note'      => 'nullable|string|max:500',
        ]);

        if (! $this->isVendorAllowed($request, (int) $data['vendor_id'])) {
            abort(403, __('Vendor is not available for this property area.'));
        }

        try {
            $updated = $this->service->assignVendor(
                $request,
                (int) $data['vendor_id'],
                $data['note'] ?? null,
                $this->agentCustomerId()
            );

            $notifyOutput = $this->notifyVendorAssigned($updated);
            $payload = $this->format($updated, true);
            $payload['whatsapp_result'] = $notifyOutput['result'] ?? null;
            if (! empty($notifyOutput['fallback_wa_me_url'])) {
                $payload['vendor_fallback_wa_me_url'] = $notifyOutput['fallback_wa_me_url'];
                if (is_array($payload['whatsapp_result'])) {
                    $payload['whatsapp_result']['fallback_wa_me_url'] = $notifyOutput['fallback_wa_me_url'];
                }
            }

            return response()->json([
                'message' => __('Vendor assigned.'),
                'data'    => $payload,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Agent maintenance vendor assign failed', [
                'request_id' => $id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json(['message' => __('Unable to assign vendor.')], 500);
        }
    }

    /** POST /api/agent/maintenance/{id}/cost */
    public function setCost(Request $httpRequest, int $id): JsonResponse
    {
        $request = $this->resolveAccessibleRequest($id);
        $data    = $httpRequest->validate([
            'estimated_cost' => 'nullable|integer|min:0',
            'actual_cost'    => 'nullable|integer|min:0',
            'cost_bearer'    => 'required|in:owner,tenant,sukoon,pending',
            'cost_recovery'  => 'required|in:next_invoice,deposit_deduction,direct_payment,none',
            'cost_settled'   => 'boolean',
        ]);
        $data['cost_settled'] = $httpRequest->boolean('cost_settled');

        try {
            $updated = $this->service->setCost($request, $data, $this->agentCustomerId());

            return response()->json([
                'message' => __('Cost updated.'),
                'data'    => $this->format($updated, true),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Agent maintenance cost update failed', [
                'request_id' => $id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json(['message' => __('Unable to update cost.')], 500);
        }
    }

    /** POST /api/agent/maintenance/{id}/responsibility */
    public function reclassifyResponsibility(Request $httpRequest, int $id): JsonResponse
    {
        $request = $this->resolveAccessibleRequest($id);
        $data    = $httpRequest->validate([
            'responsibility' => 'required|in:owner,tenant',
        ]);

        try {
            $updated = $this->responsibilityService->reclassifyRequest(
                $request,
                $data['responsibility'],
                MaintenanceResponsibilityService::ACTOR_AGENT,
                $this->agentCustomerId()
            );

            $note = $this->responsibilityService->reclassifyAuditNote($updated, MaintenanceResponsibilityService::ACTOR_AGENT);
            $this->service->addUpdate($updated, null, $note, null, MaintenanceUpdateActorService::TYPE_AGENT, $this->agentCustomerId());

            if ($this->responsibilityService->shouldRouteToAgentAfterReclassify($updated)) {
                $propertyId = (int) ($updated->property_id ?? $updated->agreement?->property_id ?? 0);
                if ($propertyId > 0) {
                    $this->service->rerouteToAreaAgent($updated, $propertyId);
                    $updated = $updated->fresh();
                }
            }

            return response()->json([
                'message' => __('Responsibility updated.'),
                'data'    => $this->format($updated, true),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Agent maintenance reclassify failed', [
                'request_id' => $id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json(['message' => __('Unable to set responsibility.')], 422);
        }
    }

    private function resolveAccessibleRequest(int $id): MaintenanceRequest
    {
        $agentId = $this->agentCustomerId();

        $request = MaintenanceRequest::query()
            ->with(['vendor', 'photos', 'updates', 'agreement'])
            ->where('id', $id)
            ->first();

        if (! $request || ! $this->dashboardQueries->agentCanManageMaintenance($agentId, $request)) {
            abort(403, __('You can only manage maintenance for your assigned or listed properties.'));
        }

        return $request;
    }

    /**
     * @return array{area: list<array<string, mixed>>, all: list<array<string, mixed>>}
     */
    private function vendorListsForRequest(MaintenanceRequest $request): array
    {
        $propertyId = (int) ($request->property_id ?? $request->agreement?->property_id ?? 0);
        $area       = [];
        $all        = [];

        try {
            if ($propertyId > 0 && class_exists(AreaRoutingService::class)) {
                $routing = app(AreaRoutingService::class);
                $area    = array_map(
                    fn (MaintenanceVendor $v) => $this->formatVendor($v),
                    $routing->activeVendorsForProperty($propertyId, $request->category, (int) ($request->subcategory_id ?? 0) ?: null)
                );
            }

            $all = MaintenanceVendor::active()
                ->where(function ($q) use ($request) {
                    $q->where('category', $request->category)->orWhere('category', 'other');
                })
                ->orderBy('name')
                ->get()
                ->map(fn (MaintenanceVendor $v) => $this->formatVendor($v))
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Agent maintenance vendors list failed', ['error' => $e->getMessage()]);
        }

        return ['area' => $area, 'all' => $all];
    }

    private function isVendorAllowed(MaintenanceRequest $request, int $vendorId): bool
    {
        $lists = $this->vendorListsForRequest($request);
        $ids   = array_merge(
            array_column($lists['area'], 'id'),
            array_column($lists['all'], 'id')
        );

        return in_array($vendorId, array_map('intval', $ids), true);
    }

    private function formatVendor(MaintenanceVendor $v): array
    {
        return [
            'id'       => $v->id,
            'name'     => $v->name,
            'phone'    => $v->phone,
            'category' => $v->category,
            'rating'   => $v->rating,
        ];
    }

    private function format(MaintenanceRequest $r, bool $full = false): array
    {
        $categories = $this->categoryService->labelsMap();
        $base       = [
            'id'                  => $r->id,
            'request_number'      => $r->request_number,
            'title'               => $r->title,
            'description'         => $r->description,
            'category'            => $r->category,
            'category_label'      => $categories[$r->category] ?? $r->category,
            'subcategory_id'      => $r->subcategory_id,
            'subcategory_name'    => $r->subcategory_name,
            'subcategory_label'   => $this->subcategoryService->displayLabel($r->category, $categories[$r->category] ?? $r->category, $r->subcategory_id, $r->subcategory_name),
            'job_label'           => $this->subcategoryService->displayLabel($r->category, $categories[$r->category] ?? $r->category, $r->subcategory_id, $r->subcategory_name),
            'priority'            => $r->priority,
            'status'              => $r->status,
            'property_id'         => $r->property_id ?? $r->agreement?->property_id,
            'property_area'       => MaintenanceVendorWhatsApp::propertyAreaLabel((int) ($r->property_id ?? $r->agreement?->property_id ?? 0)),
            'coordinator_name'    => trim((string) (auth()->user()->name ?? auth()->user()->username ?? 'Sukoon')),
            'created_at'          => $r->created_at?->format('d M Y'),
            'vendor'              => $r->vendor ? [
                'id'    => $r->vendor->id,
                'name'  => $r->vendor->name,
                'phone' => $r->vendor->phone,
            ] : null,
            'estimated_cost'      => $r->estimated_cost,
            'actual_cost'         => $r->actual_cost,
            'cost_bearer'         => $r->cost_bearer,
            'cost_recovery'       => $r->cost_recovery,
            'cost_settled'        => (bool) $r->cost_settled,
            'responsibility'      => $r->responsibility,
            'responsibility_label'=> $this->responsibilityService->responsibilityLabel((string) ($r->responsibility ?? '')),
            'awaiting_classification' => $this->responsibilityService->isAwaitingClassification((string) ($r->responsibility ?? '')),
            'routed_to'           => $r->routed_to,
            'owner_action_choice' => $r->owner_action_choice,
            'responsibility_reclassified_at' => $r->responsibility_reclassified_at?->format('d M Y H:i'),
            'responsibility_reclassified_by_type' => $r->responsibility_reclassified_by_type,
        ];

        $tenancyRef = app(MaintenanceTenancyDisplayService::class)->forRequest($r);
        $base['tenant_name']       = $tenancyRef['tenant_name'];
        $base['agreement_number']  = $tenancyRef['agreement_number'];
        $base['kyc_only']          = $tenancyRef['kyc_only'];
        $base['tenancy_display']   = $tenancyRef['display'];

        if ($full) {
            $base['updates'] = $r->updates->map(fn ($u) => [
                'note'   => $u->note,
                'status' => $u->status,
                'date'   => $u->created_at?->format('d M Y H:i'),
            ])->values()->all();
            $base['photos'] = $r->photos->map(fn ($p) => [
                'type'    => $p->type,
                'caption' => $p->caption,
            ])->values()->all();
        }

        return $base;
    }

    private function notifyVendorAssigned(MaintenanceRequest $request): array
    {
        $vendorPhone = (string) ($request->vendor?->phone ?? '');
        if ($vendorPhone === '') {
            return [
                'result' => [
                    'status' => 'failed',
                    'badge' => 'danger',
                    'message' => 'Failed - vendor phone missing',
                    'reason' => 'vendor_phone_missing',
                ],
                'fallback_wa_me_url' => null,
            ];
        }

        $categoryLabel = (string) ($this->categoryService->labelsMap()[$request->category] ?? $request->category ?? '');
        $subcategory = (string) ($request->subcategory_name ?: $request->title ?: '');
        $jobLabel = trim($categoryLabel . ' - ' . $subcategory, ' -');
        $area = MaintenanceVendorWhatsApp::propertyAreaLabel((int) ($request->property_id ?? $request->agreement?->property_id ?? 0));
        $priority = (string) ($request->priority ?? 'normal');
        $agentName = trim((string) (auth()->user()->name ?? auth()->user()->username ?? 'Sukoon'));
        $requestRef = (string) ($request->request_number ?? ('MR-' . $request->id));
        $context = $this->responsibilityContactContext($request);
        $agentWithContext = $context !== '' ? ($agentName . ' | ' . $context) : $agentName;

        $result = \Whatsapp::notify('vendor_assigned', [
                'phone' => $vendorPhone,
                'customer_type' => 'vendor',
                'customer_id' => $request->vendor_id,
            ], [
                $jobLabel,
                $area !== '' ? $area : '-',
                $priority,
                $agentWithContext,
                $requestRef,
            ]);

        if (($result['status'] ?? '') === 'sent') {
            return [
                'result' => $result,
                'fallback_wa_me_url' => null,
            ];
        }

        $fallbackMessage = MaintenanceVendorWhatsApp::buildCoordinationMessage(
            $request,
            $categoryLabel,
            $subcategory,
            (int) ($request->property_id ?? $request->agreement?->property_id ?? 0),
            $agentName
        );

        $fallbackUrl = MaintenanceVendorWhatsApp::buildUrl($vendorPhone, $fallbackMessage);
        if ($fallbackUrl) {
            $result = [
                'status' => 'fallback',
                'badge' => 'warning',
                'message' => 'Fallback - use wa.me link',
                'reason' => $result['reason'] ?? 'fallback_to_wa_me',
            ];
        }

        return [
            'result' => $result,
            'fallback_wa_me_url' => $fallbackUrl,
        ];
    }

    private function notifyMaintenanceResolved(MaintenanceRequest $request): ?array
    {
        try {
            $tenantPhone = preg_replace('/\D+/', '', (string) ($request->agreement?->tenant_phone ?? ''));
            if ($tenantPhone === '' && (int) ($request->customer_id ?? 0) > 0) {
                $tenant = \App\Models\Customer::query()->find((int) $request->customer_id);
                $tenantPhone = preg_replace('/\D+/', '', (string) ($tenant?->mobile ?? $tenant?->phone ?? ''));
            }

            if ($tenantPhone === '') {
                return [
                    'status' => 'failed',
                    'badge' => 'danger',
                    'message' => 'Failed - tenant phone missing',
                    'reason' => 'tenant_phone_missing',
                ];
            }

            $repairTitle = (string) ($request->subcategory_name ?: $request->title ?: $request->category ?: __('Maintenance'));
            $requestRef = (string) ($request->request_number ?? ('MR-' . $request->id));

            return \Whatsapp::notify('maintenance_resolved', [
                'phone' => $tenantPhone,
                'customer_type' => 'tenant',
                'customer_id' => $request->customer_id,
            ], [
                $repairTitle,
                $requestRef,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Maintenance resolved WhatsApp notify failed', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
            return [
                'status' => 'failed',
                'badge' => 'danger',
                'message' => 'Failed - ' . $e->getMessage(),
                'reason' => 'exception',
            ];
        }
    }

    private function responsibilityContactContext(MaintenanceRequest $request): string
    {
        try {
            $coordination = app(MaintenanceCoordinationService::class)->coordinationForRequest($request);
            $primary = $coordination['primary'] ?? null;
            if (! is_array($primary) || empty($primary['phone'])) {
                return '';
            }

            $role = (($primary['role'] ?? '') === 'owner') ? 'Owner' : 'Tenant';
            $name = trim((string) ($primary['name'] ?? ''));
            $phone = trim((string) ($primary['phone'] ?? ''));
            if ($phone === '') {
                return '';
            }

            return $name !== '' ? ($role . ': ' . $name . ' ' . $phone) : ($role . ': ' . $phone);
        } catch (\Throwable) {
            return '';
        }
    }
}

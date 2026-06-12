<?php

namespace App\Plugins\Maintenance\Services;

use App\Models\Customer;
use App\Plugins\Maintenance\Models\MaintenanceRequest;
use App\Plugins\Maintenance\Models\MaintenanceResponsibilityConfig;
use App\Plugins\OwnerDashboard\Models\OwnerTenancy;
use App\Plugins\RentalAgreement\Models\RentalAgreement;
use Illuminate\Support\Facades\Schema;

/**
 * Responsibility-based contact visibility for vendor coordination (admin/agent).
 */
class MaintenanceCoordinationService
{
    public function isOwnerResponsibility(MaintenanceRequest $request): bool
    {
        return (string) $request->responsibility === MaintenanceResponsibilityConfig::RESPONSIBILITY_OWNER;
    }

    public function isTenantResponsibility(MaintenanceRequest $request): bool
    {
        return (string) $request->responsibility === MaintenanceResponsibilityConfig::RESPONSIBILITY_TENANT;
    }

    /**
     * @return array{
     *   payer: string,
     *   payer_label: string,
     *   primary: ?array{name: string, phone: string, role: string},
     *   secondary: ?array{name: string, phone: string, role: string},
     *   show_tenant_to_vendor: bool,
     *   show_owner_to_vendor: bool
     * }
     */
    public function coordinationForRequest(MaintenanceRequest $request): array
    {
        $request->loadMissing('agreement');
        $agreement = $request->agreement;

        $tenant = [
            'name'  => trim((string) ($agreement?->tenant_name ?? '')),
            'phone' => $this->normalizePhone($agreement?->tenant_phone ?? null),
            'role'  => 'tenant',
        ];

        $owner = [
            'name'  => trim((string) ($agreement?->owner_name ?? '')),
            'phone' => $this->normalizePhone($agreement?->owner_phone ?? null),
            'role'  => 'owner',
        ];

        if ($owner['phone'] === '' || $owner['name'] === '') {
            $ownerCustomer = $this->ownerCustomerForRequest($request);
            if ($ownerCustomer) {
                $owner['name']  = $owner['name'] !== '' ? $owner['name'] : trim((string) ($ownerCustomer->name ?? ''));
                $owner['phone'] = $owner['phone'] !== '' ? $owner['phone'] : $this->normalizePhone($ownerCustomer->mobile ?? $ownerCustomer->phone ?? null);
            }
        }

        if ($this->isOwnerResponsibility($request)) {
            return [
                'payer'                 => 'owner',
                'payer_label'           => __('Owner pays'),
                'primary'               => $owner['phone'] !== '' ? $owner : null,
                'secondary'             => $tenant['phone'] !== '' ? $tenant : null,
                'show_tenant_to_vendor' => true,
                'show_owner_to_vendor'  => true,
            ];
        }

        if ($this->isTenantResponsibility($request)) {
            return [
                'payer'                 => 'tenant',
                'payer_label'           => __('Tenant pays'),
                'primary'               => $tenant['phone'] !== '' ? $tenant : null,
                'secondary'             => null,
                'show_tenant_to_vendor' => true,
                'show_owner_to_vendor'  => false,
            ];
        }

        return [
            'payer'                 => 'pending',
            'payer_label'           => __('Payer pending classification'),
            'primary'               => $tenant['phone'] !== '' ? $tenant : null,
            'secondary'             => null,
            'show_tenant_to_vendor' => true,
            'show_owner_to_vendor'  => false,
        ];
    }

    public function ownerCustomerIdForRequest(MaintenanceRequest $request): ?int
    {
        if ((int) ($request->routed_owner_customer_id ?? 0) > 0) {
            return (int) $request->routed_owner_customer_id;
        }

        $customer = $this->ownerCustomerForRequest($request);

        return $customer?->id ? (int) $customer->id : null;
    }

    private function ownerCustomerForRequest(MaintenanceRequest $request): ?Customer
    {
        $ownerId = null;

        if (class_exists(OwnerTenancy::class) && Schema::hasTable('owner_tenancies')) {
            $q = OwnerTenancy::query()->whereIn('status', OwnerTenancy::VISIBLE_STATUSES);
            if ($request->agreement_id) {
                $q->where('rental_agreement_id', $request->agreement_id);
            } elseif ($request->property_id) {
                $q->where('property_id', $request->property_id);
            }
            $ownerId = (int) $q->value('owner_customer_id');
        }

        if ($ownerId <= 0 && $request->property_id && Schema::hasTable('propertys')) {
            $ownerId = (int) (\Illuminate\Support\Facades\DB::table('propertys')
                ->where('id', $request->property_id)
                ->value('owner_customer_id') ?? 0);
        }

        if ($ownerId <= 0) {
            return null;
        }

        return Customer::query()->find($ownerId);
    }

    private function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        return $digits !== '' ? $digits : '';
    }
}

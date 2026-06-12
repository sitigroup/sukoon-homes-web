<?php

namespace App\Plugins\Whatsapp\Services;

use App\Models\Customer;
use App\Models\Property;
use App\Plugins\Whatsapp\Models\WaContact;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class WhatsappInboxContextService
{
    /**
     * @return array{
     *   customer: ?array{id:int,name:string,phone:string,role:?string,url:?string},
     *   property: ?array{id:int,title:string,url:?string},
     *   tenancy: ?array{id:int,status:string,url:?string},
     *   agreement: ?array{id:int,status:string,url:?string},
     *   maintenance: ?array{id:int,label:string,status:string,url:?string}
     * }
     */
    public function forContact(?WaContact $contact): array
    {
        $empty = [
            'customer' => null,
            'property' => null,
            'tenancy' => null,
            'agreement' => null,
            'maintenance' => null,
        ];

        if (! $contact) {
            return $empty;
        }

        try {
            return $this->build($contact);
        } catch (\Throwable $e) {
            Log::warning('whatsapp.inbox_context_failed', [
                'contact_id' => $contact->id,
                'message' => $e->getMessage(),
            ]);

            return $empty;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function build(WaContact $contact): array
    {
        $customer = $this->resolveCustomer($contact);
        $tenancy = $customer ? $this->resolveTenancy($customer, $contact->customer_type) : null;
        $property = $tenancy?->property_id ? Property::query()->find($tenancy->property_id) : null;
        $agreement = $this->resolveAgreement($tenancy, $customer);
        $maintenance = $this->resolveOpenMaintenance($customer, $tenancy);

        return [
            'customer' => $customer ? [
                'id' => (int) $customer->id,
                'name' => trim((string) $customer->name) ?: $this->formatPhone($contact->phone),
                'phone' => $this->formatPhone($contact->phone ?: $this->customerPhone($customer)),
                'role' => $this->roleLabel($contact, $customer),
                'url' => $this->routeIfExists('customer.edit', ['customer' => $customer->id]),
            ] : null,
            'property' => $property ? [
                'id' => (int) $property->id,
                'title' => trim((string) ($property->title ?? '')) ?: ('#'.$property->id),
                'url' => $this->routeIfExists('admin.property-timeline.show', ['propertyId' => $property->id]),
            ] : null,
            'tenancy' => $tenancy ? [
                'id' => (int) $tenancy->id,
                'status' => (string) ($tenancy->status ?? ''),
                'url' => $this->routeIfExists('admin.owner-tenancies.edit', ['owner_tenancy' => $tenancy->id]),
            ] : null,
            'agreement' => $agreement ? [
                'id' => (int) $agreement->id,
                'status' => (string) ($agreement->status ?? ''),
                'url' => $this->routeIfExists('admin.agreement-requests.show', ['agreement_request' => $agreement->id]),
            ] : null,
            'maintenance' => $maintenance ? [
                'id' => (int) $maintenance->id,
                'label' => (string) ($maintenance->request_number ?: ('MR-'.$maintenance->id)),
                'status' => (string) ($maintenance->status ?? ''),
                'url' => $this->routeIfExists('admin.maintenance.show', ['maintenance' => $maintenance->id]),
            ] : null,
        ];
    }

    private function resolveCustomer(WaContact $contact): ?Customer
    {
        if ($contact->customer_id) {
            $byId = Customer::query()->find($contact->customer_id);
            if ($byId) {
                return $byId;
            }
        }

        $phone = preg_replace('/\D+/', '', (string) $contact->phone);
        if ($phone === '') {
            return null;
        }

        $variants = $this->phoneVariants($phone);

        $candidates = Customer::query()
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '')
            ->where(function ($q) use ($variants) {
                foreach ($variants as $variant) {
                    $local = (strlen($variant) > 10 && str_starts_with($variant, '91'))
                        ? substr($variant, -10)
                        : $variant;
                    $q->orWhere('mobile', $local)->orWhere('mobile', $variant);
                }
            })
            ->limit(15)
            ->get();

        foreach ($candidates as $candidate) {
            if ($this->phonesMatch($phone, $this->customerPhone($candidate))) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return object|null
     */
    private function resolveTenancy(Customer $customer, ?string $customerType)
    {
        if (! class_exists(\App\Plugins\OwnerDashboard\Models\OwnerTenancy::class)) {
            return null;
        }

        $model = \App\Plugins\OwnerDashboard\Models\OwnerTenancy::class;
        $visible = defined($model.'::VISIBLE_STATUSES')
            ? $model::VISIBLE_STATUSES
            : ['active', 'notice', 'move_out'];

        $query = $model::query()->whereIn('status', $visible);

        $type = strtolower(trim((string) $customerType));
        if ($type === 'tenant') {
            $query->where('tenant_customer_id', $customer->id);
        } elseif ($type === 'owner') {
            $query->where('owner_customer_id', $customer->id);
        } elseif ($type === 'agent') {
            $query->where('agent_customer_id', $customer->id);
        } else {
            $query->where(function ($q) use ($customer) {
                $q->where('tenant_customer_id', $customer->id)
                    ->orWhere('owner_customer_id', $customer->id)
                    ->orWhere('agent_customer_id', $customer->id);
            });
        }

        return $query
            ->orderByDesc('activated_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @param  object|null  $tenancy
     */
    private function resolveAgreement($tenancy, ?Customer $customer): ?object
    {
        if (! class_exists(\App\Plugins\AreaManagement\Models\AgreementRequest::class)) {
            return null;
        }

        $model = \App\Plugins\AreaManagement\Models\AgreementRequest::class;

        if ($tenancy && ! empty($tenancy->agreement_request_id)) {
            $fromTenancy = $model::query()->find($tenancy->agreement_request_id);
            if ($fromTenancy) {
                return $fromTenancy;
            }
        }

        if (! $customer) {
            return null;
        }

        return $model::query()
            ->where(function ($q) use ($customer) {
                $q->where('tenant_customer_id', $customer->id)
                    ->orWhere('owner_customer_id', $customer->id)
                    ->orWhere('agent_customer_id', $customer->id);
            })
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @param  object|null  $tenancy
     */
    private function resolveOpenMaintenance(?Customer $customer, $tenancy): ?object
    {
        if (! class_exists(\App\Plugins\Maintenance\Models\MaintenanceRequest::class)) {
            return null;
        }

        $model = \App\Plugins\Maintenance\Models\MaintenanceRequest::class;
        $openStatuses = [
            $model::STATUS_OPEN,
            $model::STATUS_ASSIGNED,
            $model::STATUS_IN_PROGRESS,
            $model::STATUS_REOPENED,
        ];

        $query = $model::query()->whereIn('status', $openStatuses);

        if ($customer) {
            $query->where('customer_id', $customer->id);
        } elseif ($tenancy && $tenancy->property_id) {
            $query->where('property_id', $tenancy->property_id);
        } else {
            return null;
        }

        return $query->orderByDesc('id')->first();
    }

    private function roleLabel(WaContact $contact, Customer $customer): ?string
    {
        if ($contact->customer_type) {
            return ucfirst((string) $contact->customer_type);
        }

        if (! empty($customer->is_agent)) {
            return 'Agent';
        }

        return null;
    }

    private function customerPhone(Customer $customer): string
    {
        $cc = preg_replace('/\D+/', '', (string) ($customer->country_code ?? ''));
        $mobile = preg_replace('/\D+/', '', (string) $customer->getRawOriginal('mobile'));

        return $cc.$mobile;
    }

    /**
     * @return list<string>
     */
    private function phoneVariants(string $digits): array
    {
        $variants = [$digits];
        if (str_starts_with($digits, '91') && strlen($digits) > 10) {
            $variants[] = substr($digits, 2);
        }
        if (strlen($digits) === 10) {
            $variants[] = '91'.$digits;
        }

        return array_values(array_unique(array_filter($variants)));
    }

    private function phonesMatch(string $waPhone, string $customerPhone): bool
    {
        $wa = preg_replace('/\D+/', '', $waPhone);
        $cust = preg_replace('/\D+/', '', $customerPhone);
        if ($wa === '' || $cust === '') {
            return false;
        }

        if ($wa === $cust) {
            return true;
        }

        $waLocal = strlen($wa) > 10 && str_starts_with($wa, '91') ? substr($wa, -10) : $wa;
        $custLocal = strlen($cust) > 10 && str_starts_with($cust, '91') ? substr($cust, -10) : $cust;

        return $waLocal !== '' && $waLocal === $custLocal;
    }

    private function formatPhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return $digits !== '' ? '+'.$digits : '-';
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function routeIfExists(string $name, array $params): ?string
    {
        if (! Route::has($name)) {
            return null;
        }

        try {
            return route($name, $params);
        } catch (\Throwable) {
            return null;
        }
    }
}

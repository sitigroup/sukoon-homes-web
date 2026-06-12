<?php

namespace App\Plugins\Whatsapp\Services;

use App\Models\Customer;
use App\Plugins\Maintenance\Models\MaintenanceRequest;
use App\Plugins\Maintenance\Models\MaintenanceResponsibilityConfig;
use App\Plugins\Maintenance\Services\MaintenanceCategoryService;
use App\Plugins\Maintenance\Services\MaintenanceCoordinationService;
use App\Plugins\RentPayment\Models\RentInvoice;
use App\Plugins\RentalAgreement\Models\RentalAgreement;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Platform WhatsApp template events (try/catch — never blocks core flows).
 */
class WhatsappPlatformEventService
{
    public function notifyAgreementReady(RentalAgreement $agreement): void
    {
        try {
            $agreement->refresh();
            $ref = trim((string) ($agreement->agreement_number ?? ''));
            if ($ref === '') {
                $ref = 'RA-' . $agreement->id;
            }

            $vars = [$ref];

            $ownerPhone = $this->normalizePhone($agreement->owner_phone);
            $ownerCustomerId = $this->resolveOwnerCustomerId($agreement);
            if ($ownerPhone === '' && $ownerCustomerId) {
                $ownerPhone = $this->phoneForCustomerId($ownerCustomerId);
            }
            $this->dispatchNotify('agreement_ready', 'owner', $ownerPhone, $ownerCustomerId, $vars, [
                'agreement_id' => $agreement->id,
            ]);

            $tenantPhone = $this->normalizePhone($agreement->tenant_phone);
            $tenantCustomerId = (int) ($agreement->customer_id ?? 0) ?: $this->resolveTenantCustomerId($agreement);
            if ($tenantPhone === '' && $tenantCustomerId > 0) {
                $tenantPhone = $this->phoneForCustomerId($tenantCustomerId);
            }
            $this->dispatchNotify('agreement_ready', 'tenant', $tenantPhone, $tenantCustomerId > 0 ? $tenantCustomerId : null, $vars, [
                'agreement_id' => $agreement->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp agreement_ready failed', [
                'agreement_id' => $agreement->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function notifyMaintenanceAssigned(MaintenanceRequest $request): void
    {
        try {
            $request->loadMissing(['agreement']);

            $ref = trim((string) ($request->request_number ?? ''));
            if ($ref === '') {
                $ref = 'MR-' . $request->id;
            }

            $vars = [$ref, $this->repairDescription($request)];

            $tenantPhone = $this->normalizePhone($request->agreement?->tenant_phone ?? '');
            $tenantCustomerId = (int) ($request->customer_id ?? 0);
            if ($tenantPhone === '' && $tenantCustomerId > 0) {
                $tenantPhone = $this->phoneForCustomerId($tenantCustomerId);
            }
            $this->dispatchNotify('maintenance_assigned', 'tenant', $tenantPhone, $tenantCustomerId > 0 ? $tenantCustomerId : null, $vars, [
                'request_id' => $request->id,
            ]);

            $ownerPhone = $this->normalizePhone($request->agreement?->owner_phone ?? '');
            $ownerCustomerId = null;
            if ($request->agreement) {
                $ownerCustomerId = $this->resolveOwnerCustomerId($request->agreement);
            }
            if ($ownerPhone === '' && $ownerCustomerId) {
                $ownerPhone = $this->phoneForCustomerId($ownerCustomerId);
            }
            if ($ownerPhone !== '' && $ownerPhone !== $tenantPhone) {
                $this->dispatchNotify('maintenance_assigned', 'owner', $ownerPhone, $ownerCustomerId, $vars, [
                    'request_id' => $request->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('WhatsApp maintenance_assigned failed', [
                'request_id' => $request->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function notifyAgreementRenewal(RentalAgreement $agreement, ?int $daysRemaining = null): void
    {
        try {
            $ref = trim((string) ($agreement->agreement_number ?? ''));
            if ($ref === '') {
                $ref = 'RA-' . $agreement->id;
            }

            if ($daysRemaining !== null && $agreement->end_date) {
                $ref = $ref . ' — ends ' . $agreement->end_date->format('d M Y') . ' (' . $daysRemaining . 'd)';
            }

            $vars = [$ref];

            $tenantPhone = $this->normalizePhone($agreement->tenant_phone);
            $tenantCustomerId = (int) ($agreement->customer_id ?? 0) ?: $this->resolveTenantCustomerId($agreement);
            if ($tenantPhone === '' && $tenantCustomerId > 0) {
                $tenantPhone = $this->phoneForCustomerId($tenantCustomerId);
            }
            $this->dispatchNotify('agreement_renewal', 'tenant', $tenantPhone, $tenantCustomerId > 0 ? $tenantCustomerId : null, $vars, [
                'agreement_id' => $agreement->id,
            ]);

            $ownerPhone = $this->normalizePhone($agreement->owner_phone);
            $ownerCustomerId = $this->resolveOwnerCustomerId($agreement);
            if ($ownerPhone === '' && $ownerCustomerId) {
                $ownerPhone = $this->phoneForCustomerId($ownerCustomerId);
            }
            if ($ownerPhone !== '' && $ownerPhone !== $tenantPhone) {
                $this->dispatchNotify('agreement_renewal', 'owner', $ownerPhone, $ownerCustomerId, $vars, [
                    'agreement_id' => $agreement->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('WhatsApp agreement_renewal failed', [
                'agreement_id' => $agreement->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function notifyRentReceived(RentInvoice $invoice): void
    {
        try {
            $invoice->loadMissing(['agreement']);

            $ref = trim((string) ($invoice->invoice_number ?? ''));
            if ($ref === '') {
                $ref = 'INV-' . $invoice->id;
            }

            $vars = [
                $ref,
                $invoice->formattedTotal(),
                $invoice->periodLabel(),
            ];

            $tenantPhone = $this->normalizePhone($invoice->agreement?->tenant_phone ?? '');
            $tenantCustomerId = (int) ($invoice->customer_id ?? 0);
            if ($tenantPhone === '' && $tenantCustomerId > 0) {
                $tenantPhone = $this->phoneForCustomerId($tenantCustomerId);
            }

            $this->dispatchNotify('rent_received', 'tenant', $tenantPhone, $tenantCustomerId > 0 ? $tenantCustomerId : null, $vars, [
                'invoice_id' => $invoice->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp rent_received failed', [
                'invoice_id' => $invoice->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function notifyOwnerPaymentRequestIfApplicable(MaintenanceRequest $request): void
    {
        try {
            $request->loadMissing('agreement');

            if ((string) ($request->responsibility ?? '') !== MaintenanceResponsibilityConfig::RESPONSIBILITY_OWNER) {
                return;
            }

            if (! in_array((string) $request->status, [
                MaintenanceRequest::STATUS_RESOLVED,
                MaintenanceRequest::STATUS_CLOSED,
            ], true)) {
                return;
            }

            $amount = $this->resolvePayableAmount($request);
            if ($amount <= 0) {
                return;
            }

            $coordination = app(MaintenanceCoordinationService::class);
            $ownerCustomerId = $coordination->ownerCustomerIdForRequest($request);
            $primary = $coordination->coordinationForRequest($request)['primary'] ?? null;

            $phone = '';
            if (is_array($primary) && ($primary['role'] ?? '') === 'owner') {
                $phone = $this->normalizePhone($primary['phone'] ?? '');
            }
            if ($phone === '' && $ownerCustomerId) {
                $phone = $this->phoneForCustomerId($ownerCustomerId);
            }
            if ($phone === '') {
                $phone = $this->normalizePhone($request->agreement?->owner_phone ?? '');
            }

            if ($phone === '') {
                Log::info('WhatsApp owner_payment_request skipped — owner phone missing', [
                    'request_id' => $request->id,
                ]);

                return;
            }

            $description = $this->repairDescription($request);
            $amountLabel = 'Rs. ' . number_format($amount, 0, '.', ',');
            $ref = trim((string) ($request->request_number ?? ''));
            if ($ref === '') {
                $ref = 'MR-' . $request->id;
            }

            $this->dispatchNotify('owner_payment_request', 'owner', $phone, $ownerCustomerId, [
                $description,
                $amountLabel,
                $ref,
            ], [
                'request_id' => $request->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp owner_payment_request failed', [
                'request_id' => $request->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function dispatchNotify(
        string $eventKey,
        string $customerType,
        string $phone,
        ?int $customerId,
        array $vars,
        array $logContext = []
    ): void {
        if ($phone === '') {
            return;
        }

        try {
            \Whatsapp::notify($eventKey, [
                'phone' => $phone,
                'customer_type' => $customerType,
                'customer_id' => $customerId,
            ], $vars);
        } catch (\Throwable $e) {
            Log::warning("WhatsApp {$eventKey} dispatch failed", array_merge($logContext, [
                'customer_type' => $customerType,
                'error' => $e->getMessage(),
            ]));
        }
    }

    private function repairDescription(MaintenanceRequest $request): string
    {
        $sub = trim((string) ($request->subcategory_name ?? ''));
        if ($sub !== '') {
            return $sub;
        }

        $title = trim((string) ($request->title ?? ''));
        if ($title !== '') {
            return $title;
        }

        try {
            $labels = app(MaintenanceCategoryService::class)->labelsMap();
            $cat = (string) ($request->category ?? '');

            return (string) ($labels[$cat] ?? $cat ?: __('Maintenance'));
        } catch (\Throwable) {
            return (string) ($request->category ?? __('Maintenance'));
        }
    }

    private function resolvePayableAmount(MaintenanceRequest $request): float
    {
        $actual = (float) ($request->actual_cost ?? 0);
        if ($actual > 0) {
            return $actual;
        }

        return (float) ($request->estimated_cost ?? 0);
    }

    private function resolveOwnerCustomerId(RentalAgreement $agreement): ?int
    {
        if (Schema::hasTable('agreement_requests')) {
            $id = \Illuminate\Support\Facades\DB::table('agreement_requests')
                ->where('created_agreement_id', $agreement->id)
                ->value('owner_customer_id');
            if ($id) {
                return (int) $id;
            }
        }

        if ($agreement->property_id && Schema::hasTable('propertys')) {
            $id = \Illuminate\Support\Facades\DB::table('propertys')
                ->where('id', $agreement->property_id)
                ->value('owner_customer_id');
            if ($id) {
                return (int) $id;
            }
        }

        return null;
    }

    private function resolveTenantCustomerId(RentalAgreement $agreement): ?int
    {
        if (Schema::hasTable('agreement_requests')) {
            $id = \Illuminate\Support\Facades\DB::table('agreement_requests')
                ->where('created_agreement_id', $agreement->id)
                ->value('tenant_customer_id');
            if ($id) {
                return (int) $id;
            }
        }

        return null;
    }

    private function phoneForCustomerId(int $customerId): string
    {
        if ($customerId <= 0) {
            return '';
        }

        try {
            $customer = Customer::query()->find($customerId);

            return $this->normalizePhone($customer?->mobile ?? $customer?->phone ?? '');
        } catch (\Throwable) {
            return '';
        }
    }

    private function normalizePhone(?string $phone): string
    {
        return \App\Plugins\Whatsapp\Support\WhatsappPhoneHelper::toMetaRecipient($phone);
    }
}

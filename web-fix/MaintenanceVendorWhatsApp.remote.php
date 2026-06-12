<?php

namespace App\Plugins\Maintenance\Support;

use App\Plugins\Maintenance\Models\MaintenanceRequest;
use App\Plugins\Maintenance\Services\MaintenanceCoordinationService;

class MaintenanceVendorWhatsApp
{
    public static function formatPhoneForWaMe(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) === 10) {
            return '91' . $digits;
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '91' . substr($digits, 1);
        }

        return $digits;
    }

    public static function propertyAreaLabel(int $propertyId): string
    {
        if ($propertyId <= 0 || ! \Illuminate\Support\Facades\Schema::hasTable('area_listing_property_locations')) {
            return '';
        }

        try {
            $loc = \Illuminate\Support\Facades\DB::table('area_listing_property_locations')
                ->where('property_id', $propertyId)
                ->first();

            if (! $loc) {
                return '';
            }

            $area = trim((string) ($loc->area_name ?? $loc->detected_area_name ?? ''));
            if ($area !== '') {
                return $area;
            }

            return trim((string) ($loc->city ?? ''));
        } catch (\Throwable $e) {
            return '';
        }
    }

    public static function buildMessage(
        string $title,
        string $category,
        string $subcategory,
        string $area,
        string $priority,
        string $coordinatorName,
        string $requestNumber
    ): string {
        $sub = $subcategory !== '' ? $subcategory : $title;

        return __('Job: :category — :subcategory at :area. Issue: :title. Priority: :priority. Please contact :coordinator to coordinate. Ref: :ref', [
            'category'    => $category !== '' ? $category : '—',
            'subcategory' => $sub !== '' ? $sub : '—',
            'area'        => $area !== '' ? $area : '—',
            'title'       => $title !== '' ? $title : '—',
            'priority'    => $priority !== '' ? $priority : '—',
            'coordinator' => $coordinatorName !== '' ? $coordinatorName : 'Sukoon',
            'ref'         => $requestNumber !== '' ? $requestNumber : '—',
        ]);
    }

    /**
     * Responsibility-aware vendor message with payer contact number.
     */
    public static function buildCoordinationMessage(
        MaintenanceRequest $request,
        string $categoryLabel,
        string $subcategoryLabel,
        int $propertyId,
        string $coordinatorName,
    ): string {
        $coordination = app(MaintenanceCoordinationService::class)->coordinationForRequest($request);
        $area         = self::propertyAreaLabel($propertyId);
        $sub          = $subcategoryLabel !== '' ? $subcategoryLabel : $request->title;

        $lines = [
            __('Sukoon Homes — maintenance :ref', ['ref' => $request->request_number]),
            __('Job: :category — :sub at :area. Issue: :title. Priority: :priority.', [
                'category' => $categoryLabel !== '' ? $categoryLabel : '—',
                'sub'      => $sub !== '' ? $sub : '—',
                'area'     => $area !== '' ? $area : '—',
                'title'    => $request->title,
                'priority' => $request->priority,
            ]),
        ];

        $primary = $coordination['primary'] ?? null;
        if ($primary && ($primary['phone'] ?? '') !== '') {
            $lines[] = __('Contact :role :name — :phone', [
                'role'  => $primary['role'] === 'owner' ? __('Owner') : __('Tenant'),
                'name'  => $primary['name'] !== '' ? $primary['name'] : __('Contact'),
                'phone' => $primary['phone'],
            ]);
        }

        if (! empty($coordination['show_tenant_to_vendor']) && ! empty($coordination['secondary'])) {
            $sec = $coordination['secondary'];
            if (($sec['phone'] ?? '') !== '' && ($primary['phone'] ?? '') !== ($sec['phone'] ?? '')) {
                $lines[] = __('Also tenant :name — :phone', [
                    'name'  => $sec['name'] !== '' ? $sec['name'] : __('Tenant'),
                    'phone' => $sec['phone'],
                ]);
            }
        }

        $lines[] = __('Coordinator: :name', ['name' => $coordinatorName !== '' ? $coordinatorName : 'Sukoon']);

        return implode("\n", $lines);
    }

    public static function buildUrl(?string $phone, string $message): ?string
    {
        $formatted = self::formatPhoneForWaMe($phone);
        if (! $formatted || $message === '') {
            return null;
        }

        return 'https://wa.me/' . $formatted . '?text=' . rawurlencode($message);
    }
}

<?php

namespace App\Plugins\TrustVerification\Services\Automation;

use App\Plugins\TrustVerification\Models\TvOrder;

class RulesVerificationProvider implements VerificationProviderInterface
{
    public function key(): string
    {
        return 'rules';
    }

    public function label(): string
    {
        return 'Built-in rules (ID format, phone, address)';
    }

    public function run(TvOrder $order, array $settings): array
    {
        $order->loadMissing(['subject', 'checkItems', 'documents']);
        $subject = $order->subject;
        $results = [];

        foreach ($order->checkItems as $item) {
            if ($item->status === 'na') {
                continue;
            }

            $result = match ($item->check_key) {
                'id_verification' => $this->evaluateId($subject?->id_type, $subject?->id_number_hint, $order),
                'address_validation', 'address_match' => $this->evaluateAddress($subject?->current_address, $subject?->property_address, $order->order_type),
                'reference_check' => $this->evaluatePhone($subject?->phone),
                default => ['status' => 'pending', 'notes' => 'Requires manual or vendor verification'],
            };

            $results[] = [
                'check_key' => $item->check_key,
                'status' => $result['status'],
                'notes' => $result['notes'],
            ];
        }

        return $results;
    }

    /** @return array{status: string, notes: string} */
    private function evaluateId(?string $idType, ?string $idHint, TvOrder $order): array
    {
        $hasDoc = $order->documents->whereIn('doc_type', ['id_front', 'pan'])->isNotEmpty();

        if (! $hasDoc) {
            return [
                'status' => 'pending',
                'notes' => 'Awaiting ID document upload from customer.',
            ];
        }

        $digits = preg_replace('/\D/', '', (string) $idHint);

        if (stripos((string) $idType, 'aadhaar') !== false) {
            if (strlen($digits) === 12) {
                return [
                    'status' => 'pass',
                    'notes' => 'Aadhaar format valid (12 digits). Ops must verify document image.',
                ];
            }

            return [
                'status' => 'fail',
                'notes' => 'Aadhaar number format invalid.',
            ];
        }

        if (stripos((string) $idType, 'pan') !== false) {
            if (preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/i', strtoupper(preg_replace('/\s+/', '', (string) $idHint)))) {
                return [
                    'status' => 'pass',
                    'notes' => 'PAN format valid. Ops must verify document image.',
                ];
            }

            return [
                'status' => 'pending',
                'notes' => 'PAN format could not be validated from masked hint — verify manually.',
            ];
        }

        return [
            'status' => 'pending',
            'notes' => 'ID type requires manual verification.',
        ];
    }

    /** @return array{status: string, notes: string} */
    private function evaluateAddress(?string $current, ?string $property, string $orderType): array
    {
        $address = $orderType === 'owner' ? ($property ?: $current) : $current;
        $address = trim((string) $address);

        if (strlen($address) < 10) {
            return [
                'status' => 'pending',
                'notes' => 'Address missing or too short — collect from customer.',
            ];
        }

        if (strlen($address) >= 20) {
            return [
                'status' => 'pass',
                'notes' => 'Address provided and looks complete. Ops may spot-check.',
            ];
        }

        return [
            'status' => 'pending',
            'notes' => 'Address provided but may need verification.',
        ];
    }

    /** @return array{status: string, notes: string} */
    private function evaluatePhone(?string $phone): array
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        if (preg_match('/^[6-9]\d{9}$/', $digits)) {
            return [
                'status' => 'pass',
                'notes' => 'Valid 10-digit Indian mobile format.',
            ];
        }

        return [
            'status' => 'pending',
            'notes' => 'Phone format could not be validated.',
        ];
    }
}

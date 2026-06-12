<?php

namespace App\Plugins\Whatsapp\Services;

use App\Plugins\Whatsapp\Models\WaContact;

class ContactResolver
{
    public function resolveByPhone(string $phone, ?string $customerType = null, ?int $customerId = null): WaContact
    {
        return WaContact::query()->firstOrCreate(
            ['phone' => $phone],
            [
                'customer_type' => $customerType,
                'customer_id' => $customerId,
                'opted_in' => true,
            ]
        );
    }
}


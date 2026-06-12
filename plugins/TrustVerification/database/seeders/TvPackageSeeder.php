<?php

namespace App\Plugins\TrustVerification\Database\Seeders;

use App\Plugins\TrustVerification\Models\TvPackage;
use App\Plugins\TrustVerification\Services\TrustVerificationService;
use Illuminate\Database\Seeder;

class TvPackageSeeder extends Seeder
{
    public function run(): void
    {
        $city = TrustVerificationService::CITY_BARMER;

        $packages = [
            [
                'type' => 'tenant',
                'city_slug' => $city,
                'name' => 'Tenant Basic',
                'slug' => 'tenant-basic-barmer',
                'price' => 999,
                'delivery_hours' => 72,
                'sort_order' => 1,
                'features' => [
                    ['key' => 'id_verification', 'label' => 'ID verification', 'included' => true],
                    ['key' => 'address_validation', 'label' => 'Address validation', 'included' => true],
                    ['key' => 'criminal_check', 'label' => 'Criminal record check', 'included' => false],
                    ['key' => 'civil_check', 'label' => 'Civil litigation check', 'included' => false],
                    ['key' => 'reference_check', 'label' => 'Previous landlord reference', 'included' => false],
                ],
            ],
            [
                'type' => 'tenant',
                'city_slug' => $city,
                'name' => 'Tenant Standard',
                'slug' => 'tenant-standard-barmer',
                'price' => 1999,
                'delivery_hours' => 48,
                'sort_order' => 2,
                'features' => [
                    ['key' => 'id_verification', 'label' => 'ID verification', 'included' => true],
                    ['key' => 'address_validation', 'label' => 'Address validation', 'included' => true],
                    ['key' => 'criminal_check', 'label' => 'Criminal record check', 'included' => true],
                    ['key' => 'civil_check', 'label' => 'Civil litigation check', 'included' => true],
                    ['key' => 'reference_check', 'label' => 'Previous landlord reference', 'included' => true],
                ],
            ],
            [
                'type' => 'owner',
                'city_slug' => $city,
                'name' => 'Owner Basic',
                'slug' => 'owner-basic-barmer',
                'price' => 999,
                'delivery_hours' => 72,
                'sort_order' => 1,
                'features' => [
                    ['key' => 'id_verification', 'label' => 'ID verification', 'included' => true],
                    ['key' => 'ownership_docs', 'label' => 'Ownership document review', 'included' => true],
                    ['key' => 'address_match', 'label' => 'Property address match', 'included' => false],
                    ['key' => 'criminal_check', 'label' => 'Criminal record check', 'included' => false],
                    ['key' => 'reference_check', 'label' => 'Reference check', 'included' => false],
                ],
            ],
            [
                'type' => 'owner',
                'city_slug' => $city,
                'name' => 'Owner Standard',
                'slug' => 'owner-standard-barmer',
                'price' => 1999,
                'delivery_hours' => 48,
                'sort_order' => 2,
                'features' => [
                    ['key' => 'id_verification', 'label' => 'ID verification', 'included' => true],
                    ['key' => 'ownership_docs', 'label' => 'Ownership document review', 'included' => true],
                    ['key' => 'address_match', 'label' => 'Property address match', 'included' => true],
                    ['key' => 'criminal_check', 'label' => 'Criminal record check', 'included' => true],
                    ['key' => 'reference_check', 'label' => 'Reference check', 'included' => true],
                ],
            ],
        ];

        foreach ($packages as $row) {
            TvPackage::updateOrCreate(
                ['slug' => $row['slug']],
                array_merge($row, ['currency' => 'INR', 'is_active' => true])
            );
        }
    }
}

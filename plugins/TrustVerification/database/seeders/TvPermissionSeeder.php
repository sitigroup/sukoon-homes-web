<?php

namespace App\Plugins\TrustVerification\Database\Seeders;

use App\Plugins\TrustVerification\Services\TrustVerificationPermissionService;
use Illuminate\Database\Seeder;

class TvPermissionSeeder extends Seeder
{
    public function run(): void
    {
        TrustVerificationPermissionService::registerModule();
    }
}

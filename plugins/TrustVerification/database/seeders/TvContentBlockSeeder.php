<?php

namespace App\Plugins\TrustVerification\Database\Seeders;

use App\Plugins\TrustVerification\Services\TrustVerificationContentService;
use Illuminate\Database\Seeder;

class TvContentBlockSeeder extends Seeder
{
    public function run(): void
    {
        TrustVerificationContentService::seedDefaults(false);
    }
}

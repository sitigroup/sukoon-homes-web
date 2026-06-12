<?php

namespace App\Plugins\TrustVerification\Services\Automation;

use App\Plugins\TrustVerification\Models\TvOrder;

class ManualVerificationProvider implements VerificationProviderInterface
{
    public function key(): string
    {
        return 'manual';
    }

    public function label(): string
    {
        return 'Manual only';
    }

    public function run(TvOrder $order, array $settings): array
    {
        return [];
    }
}

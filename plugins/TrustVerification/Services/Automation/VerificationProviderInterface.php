<?php

namespace App\Plugins\TrustVerification\Services\Automation;

use App\Plugins\TrustVerification\Models\TvOrder;

interface VerificationProviderInterface
{
    public function key(): string;

    public function label(): string;

    /** @return array<int, array{check_key: string, status: string, notes: ?string}> */
    public function run(TvOrder $order, array $settings): array;
}

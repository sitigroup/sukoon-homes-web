<?php

namespace App\Plugins\TrustVerification\Support;

use App\Plugins\TrustVerification\Services\TrustVerificationPermissionService;
use Illuminate\Http\RedirectResponse;

trait ChecksTrustVerificationPermissions
{
    protected function tvDenyUnless(string $ability): ?RedirectResponse
    {
        return TrustVerificationPermissionService::denyUnlessCan($ability);
    }
}

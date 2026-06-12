<?php

namespace App\Plugins\TrustVerification\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvTenantReliability;
use App\Plugins\TrustVerification\Services\TrustVerificationPermissionService;
use App\Plugins\TrustVerification\Services\TrustVerificationTenantReliabilityService;
use App\Plugins\TrustVerification\Support\ChecksTrustVerificationPermissions;
use Illuminate\Http\Request;

class TrustVerificationTenantReliabilityAdminController extends Controller
{
    use ChecksTrustVerificationPermissions;

    public function index(Request $request)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_READ)) {
            return $denied;
        }

        $query = TvTenantReliability::query()->orderByDesc('verification_completion');

        if ($request->filled('q') && ctype_digit(trim($request->input('q')))) {
            $query->where('customer_id', (int) $request->input('q'));
        }

        if ($level = $request->string('level')->toString()) {
            $query->where('reliability_level', $level);
        }

        $rows = $query->paginate(25)->withQueryString();

        return view('trust-verification::admin.reliability.index', [
            'rows' => $rows,
            'filters' => $request->only(['q', 'level']),
            'levels' => TrustVerificationTenantReliabilityService::LEVEL_LABELS,
        ]);
    }

    public function show(int $customerId)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_READ)) {
            return $denied;
        }

        $record = TvTenantReliability::where('customer_id', $customerId)->first();
        $ownerSafe = TrustVerificationTenantReliabilityService::publicSafeData($customerId, false);
        $orders = TvOrder::query()
            ->where('customer_id', $customerId)
            ->where('order_type', 'tenant')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'order_number', 'status', 'completed_at', 'created_at']);

        return view('trust-verification::admin.reliability.show', [
            'customerId' => $customerId,
            'record' => $record,
            'ownerSafe' => $ownerSafe,
            'orders' => $orders,
            'levels' => TrustVerificationTenantReliabilityService::LEVEL_LABELS,
        ]);
    }

    public function refreshCustomer(Request $request, int $customerId)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        TrustVerificationTenantReliabilityService::refresh($customerId, 'admin_refresh');

        return redirect()->back()->with('success', 'Tenant reliability refreshed for customer #'.$customerId);
    }

    public function bulkRefresh(Request $request)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        $count = TrustVerificationTenantReliabilityService::bulkRefreshAll();

        return redirect()->route('trust-verification.reliability.index')
            ->with('success', 'Bulk refresh completed for '.$count.' tenant customer(s).');
    }

    public function togglePublic(Request $request, int $customerId)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        $data = $request->validate([
            'public_visible' => 'required|boolean',
        ]);

        TrustVerificationTenantReliabilityService::setPublicVisible($customerId, (bool) $data['public_visible']);

        return redirect()->back()->with('success', 'Visibility updated.');
    }
}

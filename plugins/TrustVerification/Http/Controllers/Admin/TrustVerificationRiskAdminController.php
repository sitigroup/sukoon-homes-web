<?php

namespace App\Plugins\TrustVerification\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvRiskProfile;
use App\Plugins\TrustVerification\Models\TvRiskSignal;
use App\Plugins\TrustVerification\Models\TvTrustScore;
use App\Plugins\TrustVerification\Services\TrustVerificationFraudRiskService;
use App\Plugins\TrustVerification\Services\TrustVerificationPermissionService;
use App\Plugins\TrustVerification\Support\ChecksTrustVerificationPermissions;
use Illuminate\Http\Request;

class TrustVerificationRiskAdminController extends Controller
{
    use ChecksTrustVerificationPermissions;

    public function profilesIndex(Request $request)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_READ)) {
            return $denied;
        }

        $query = TvRiskProfile::query()->orderByDesc('risk_score');

        if ($level = $request->string('level')->toString()) {
            $query->where('risk_level', $level);
        }

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            if (ctype_digit($q)) {
                $query->where('customer_id', (int) $q);
            }
        }

        if ($request->boolean('duplicate_phone')) {
            $query->whereIn('customer_id', function ($sub) {
                $sub->select('customer_id')
                    ->from('tv_risk_signals')
                    ->where('signal_type', TrustVerificationFraudRiskService::SIGNAL_DUPLICATE_MOBILE);
            });
        }

        if ($request->boolean('rejected_police')) {
            $query->whereIn('customer_id', function ($sub) {
                $sub->select('customer_id')
                    ->from('tv_risk_signals')
                    ->where('signal_type', TrustVerificationFraudRiskService::SIGNAL_POLICE_REJECTED);
            });
        }

        if ($request->boolean('failed_reference')) {
            $query->whereIn('customer_id', function ($sub) {
                $sub->select('customer_id')
                    ->from('tv_risk_signals')
                    ->where('signal_type', TrustVerificationFraudRiskService::SIGNAL_REFERENCE_FAILED);
            });
        }

        $profiles = $query->paginate(25)->withQueryString();

        $customerIds = $profiles->pluck('customer_id')->all();
        $trustScores = TvTrustScore::query()
            ->whereIn('customer_id', $customerIds)
            ->pluck('trust_score', 'customer_id');
        $signalCounts = TvRiskSignal::query()
            ->whereIn('customer_id', $customerIds)
            ->selectRaw('customer_id, count(*) as cnt')
            ->groupBy('customer_id')
            ->pluck('cnt', 'customer_id');

        return view('trust-verification::admin.risk.profiles.index', [
            'profiles' => $profiles,
            'trustScores' => $trustScores,
            'signalCounts' => $signalCounts,
            'filters' => $request->only(['q', 'level', 'duplicate_phone', 'rejected_police', 'failed_reference']),
            'levels' => ['low', 'medium', 'high', 'critical'],
        ]);
    }

    public function profilesShow(int $customerId)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_READ)) {
            return $denied;
        }

        $profile = TvRiskProfile::where('customer_id', $customerId)->first();
        $signals = TvRiskSignal::query()
            ->where('customer_id', $customerId)
            ->orderByDesc('id')
            ->paginate(50, ['*'], 'signals_page');

        $trustScore = TvTrustScore::where('customer_id', $customerId)->first();
        $orders = TvOrder::query()
            ->where('customer_id', $customerId)
            ->orderByDesc('id')
            ->limit(15)
            ->get(['id', 'order_number', 'status', 'created_at']);

        return view('trust-verification::admin.risk.profiles.show', [
            'customerId' => $customerId,
            'profile' => $profile,
            'trustScore' => $trustScore,
            'signals' => $signals,
            'orders' => $orders,
            'riskPayload' => TrustVerificationFraudRiskService::formatProfileForAdmin($profile, $customerId),
        ]);
    }

    public function signalsIndex(Request $request)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_READ)) {
            return $denied;
        }

        $query = TvRiskSignal::query()->orderByDesc('id');

        if ($request->filled('customer_id')) {
            $query->where('customer_id', (int) $request->input('customer_id'));
        }

        if ($type = $request->string('signal_type')->toString()) {
            $query->where('signal_type', $type);
        }

        if ($source = $request->string('source')->toString()) {
            $query->where('source', $source);
        }

        $signals = $query->paginate(40)->withQueryString();

        return view('trust-verification::admin.risk.signals.index', [
            'signals' => $signals,
            'filters' => $request->only(['customer_id', 'signal_type', 'source']),
            'signalTypes' => array_keys(TrustVerificationFraudRiskService::DEFAULT_POINTS),
        ]);
    }

    public function refreshCustomer(Request $request, int $customerId)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        TrustVerificationFraudRiskService::refreshCustomer($customerId, 'admin_refresh');

        return redirect()->back()->with('success', 'Risk profile recalculated for customer #'.$customerId);
    }

    public function bulkRefresh(Request $request)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        $count = TrustVerificationFraudRiskService::bulkRefreshAll();

        return redirect()->route('trust-verification.risk.profiles.index')
            ->with('success', 'Risk refresh completed for '.$count.' customer(s).');
    }

    public function manualOverride(Request $request, int $customerId)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        $data = $request->validate([
            'manual_override' => 'required|integer|min:-100|max:100',
            'notes' => 'nullable|string|max:5000',
        ]);

        TrustVerificationFraudRiskService::manualOverride(
            $customerId,
            (int) $data['manual_override'],
            $data['notes'] ?? null
        );

        return redirect()->back()->with('success', 'Manual risk override saved.');
    }

    public function storeManualFlag(Request $request, int $customerId)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        $data = $request->validate([
            'risk_points' => 'required|integer|min:1|max:100',
            'notes' => 'required|string|max:2000',
            'order_id' => 'nullable|integer|exists:tv_orders,id',
        ]);

        TrustVerificationFraudRiskService::addManualFlag(
            $customerId,
            (int) $data['risk_points'],
            $data['notes'],
            isset($data['order_id']) ? (int) $data['order_id'] : null,
            auth()->id()
        );

        return redirect()->back()->with('success', 'Manual fraud flag recorded.');
    }

    public function profileApi(int $customerId)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_READ)) {
            return $denied;
        }

        return response()->json([
            'error' => false,
            'data' => TrustVerificationFraudRiskService::formatProfileForAdmin(
                TvRiskProfile::where('customer_id', $customerId)->first(),
                $customerId
            ),
        ]);
    }

    public function destroySignal(Request $request, TvRiskSignal $signal)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        if ($signal->source !== TrustVerificationFraudRiskService::SOURCE_MANUAL) {
            return redirect()->back()->with('error', 'Only manual flags can be removed from this screen.');
        }

        TrustVerificationFraudRiskService::removeManualSignal($signal);

        return redirect()->back()->with('success', 'Manual flag removed.');
    }
}

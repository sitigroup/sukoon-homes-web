<?php

namespace App\Plugins\TrustVerification\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\TrustVerification\Models\TvCustomerBadge;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvTrustBadge;
use App\Plugins\TrustVerification\Models\TvTrustScore;
use App\Plugins\TrustVerification\Services\TrustVerificationPermissionService;
use App\Plugins\TrustVerification\Services\TrustVerificationTrustBadgeService;
use App\Plugins\TrustVerification\Support\ChecksTrustVerificationPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrustVerificationTrustAdminController extends Controller
{
    use ChecksTrustVerificationPermissions;

    public function badgesIndex()
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        $badges = TvTrustBadge::query()->orderBy('priority')->orderBy('name')->get();

        return view('trust-verification::admin.trust.badges.index', compact('badges'));
    }

    public function badgesEdit(TvTrustBadge $badge)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        return view('trust-verification::admin.trust.badges.form', ['badge' => $badge]);
    }

    public function badgesUpdate(Request $request, TvTrustBadge $badge)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:2000',
            'priority' => 'required|integer|min:0|max:999',
            'active' => 'nullable|boolean',
            'auto_assign' => 'nullable|boolean',
            'color' => 'nullable|string|max:32',
            'icon' => 'nullable|string|max:64',
        ]);

        $badge->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'priority' => (int) $data['priority'],
            'active' => $request->boolean('active'),
            'auto_assign' => $request->boolean('auto_assign'),
            'color' => $data['color'] ?? '#111827',
            'icon' => $data['icon'] ?? 'bi-shield-check',
        ]);

        return redirect()->route('trust-verification.trust.badges.index')->with('success', 'Badge updated');
    }

    public function rulesIndex()
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        $weights = TrustVerificationTrustBadgeService::scoreWeights();
        $publicEnabled = TrustVerificationTrustBadgeService::isPublicTrustEnabled();

        return view('trust-verification::admin.trust.rules.index', [
            'weights' => $weights,
            'publicEnabled' => $publicEnabled,
        ]);
    }

    public function rulesUpdate(Request $request)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        $data = $request->validate([
            'identity' => 'required|integer|min:0|max:100',
            'address' => 'required|integer|min:0|max:100',
            'reference' => 'required|integer|min:0|max:100',
            'police' => 'required|integer|min:0|max:100',
            'documents' => 'required|integer|min:0|max:100',
            'admin_approval' => 'required|integer|min:0|max:100',
            'no_rejected' => 'required|integer|min:0|max:100',
            'trust_public_profile_enabled' => 'nullable|boolean',
        ]);

        TrustVerificationTrustBadgeService::saveScoreWeights([
            'identity' => (int) $data['identity'],
            'address' => (int) $data['address'],
            'reference' => (int) $data['reference'],
            'police' => (int) $data['police'],
            'documents' => (int) $data['documents'],
            'admin_approval' => (int) $data['admin_approval'],
            'no_rejected' => (int) $data['no_rejected'],
        ]);

        \App\Plugins\TrustVerification\Models\TvSetting::updateOrCreate(
            ['key' => TrustVerificationTrustBadgeService::SETTING_PUBLIC_ENABLED],
            ['value' => $request->boolean('trust_public_profile_enabled') ? '1' : '0']
        );

        return redirect()->route('trust-verification.trust.rules.index')->with('success', 'Trust rules saved');
    }

    public function customersIndex(Request $request)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_READ)) {
            return $denied;
        }

        $q = trim((string) $request->query('q', ''));

        $query = TvTrustScore::query()->orderByDesc('trust_score');

        if ($q !== '') {
            if (ctype_digit($q)) {
                $query->where('customer_id', (int) $q);
            } else {
                $query->where('customer_id', 0);
            }
        }

        $scores = $query->paginate(25)->withQueryString();

        $customerIds = $scores->pluck('customer_id')->all();
        $badgeCounts = TvCustomerBadge::query()
            ->whereIn('customer_id', $customerIds)
            ->select('customer_id', DB::raw('count(*) as total'))
            ->groupBy('customer_id')
            ->pluck('total', 'customer_id');

        return view('trust-verification::admin.trust.customers.index', [
            'scores' => $scores,
            'badgeCounts' => $badgeCounts,
            'filters' => ['q' => $q],
        ]);
    }

    public function customersShow(int $customerId)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_READ)) {
            return $denied;
        }

        $score = TvTrustScore::firstOrNew(['customer_id' => $customerId]);
        $badges = TvTrustBadge::where('active', true)->orderBy('priority')->get();
        $assigned = TvCustomerBadge::with('badge')
            ->where('customer_id', $customerId)
            ->get()
            ->keyBy('badge_id');

        $orders = TvOrder::where('customer_id', $customerId)
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'order_number', 'status', 'order_type', 'completed_at']);

        return view('trust-verification::admin.trust.customers.show', [
            'customerId' => $customerId,
            'score' => $score,
            'badges' => $badges,
            'assigned' => $assigned,
            'orders' => $orders,
            'breakdown' => $score->score_breakdown_json ?? [],
        ]);
    }

    public function recalculateCustomer(Request $request, int $customerId)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        TrustVerificationTrustBadgeService::refreshScore($customerId, 'admin_recalculate');

        return redirect()->route('trust-verification.trust.customers.show', $customerId)
            ->with('success', 'Trust score recalculated');
    }

    public function manualAdjustment(Request $request, int $customerId)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        $data = $request->validate([
            'manual_adjustment' => 'required|integer|min:-100|max:100',
        ]);

        TrustVerificationTrustBadgeService::manualAdjustment($customerId, (int) $data['manual_adjustment'], auth()->id());

        return redirect()->route('trust-verification.trust.customers.show', $customerId)
            ->with('success', 'Manual adjustment applied');
    }

    public function togglePublic(Request $request, int $customerId)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        TrustVerificationTrustBadgeService::setPublicVisible($customerId, $request->boolean('public_visible'));

        return redirect()->route('trust-verification.trust.customers.show', $customerId)
            ->with('success', 'Public visibility updated');
    }

    public function assignBadge(Request $request, int $customerId)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        $data = $request->validate([
            'badge_id' => 'required|exists:tv_trust_badges,id',
            'notes' => 'nullable|string|max:500',
        ]);

        TrustVerificationTrustBadgeService::assignBadge(
            $customerId,
            (int) $data['badge_id'],
            auth()->id(),
            false,
            $data['notes'] ?? null
        );

        TrustVerificationTrustBadgeService::refreshScore($customerId, 'badge_assigned');

        return redirect()->route('trust-verification.trust.customers.show', $customerId)
            ->with('success', 'Badge assigned');
    }

    public function revokeBadge(Request $request, int $customerId, TvCustomerBadge $customerBadge)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        if ((int) $customerBadge->customer_id !== $customerId) {
            abort(404);
        }

        $customerBadge->delete();

        TrustVerificationTrustBadgeService::refreshScore($customerId, 'badge_revoked');

        return redirect()->route('trust-verification.trust.customers.show', $customerId)
            ->with('success', 'Badge revoked');
    }

    public function bulkRefresh(Request $request)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        $count = TrustVerificationTrustBadgeService::bulkRefreshAll();

        return redirect()->route('trust-verification.trust.customers.index')
            ->with('success', 'Recalculated trust for '.$count.' customer(s)');
    }
}

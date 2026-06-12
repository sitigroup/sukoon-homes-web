<?php

namespace App\Plugins\TrustVerification\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvVerificationBadge;
use App\Plugins\TrustVerification\Services\TrustVerificationIssuedBadgeService;
use App\Plugins\TrustVerification\Services\TrustVerificationPermissionService;
use App\Plugins\TrustVerification\Support\ChecksTrustVerificationPermissions;
use Illuminate\Http\Request;

class TrustVerificationIssuedBadgeAdminController extends Controller
{
    use ChecksTrustVerificationPermissions;

    public function index(Request $request)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_READ)) {
            return $denied;
        }

        TrustVerificationIssuedBadgeService::syncExpiredStatuses();

        $query = TvVerificationBadge::query()
            ->with('order')
            ->orderByDesc('id');

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($builder) use ($q) {
                $builder->where('badge_number', 'like', '%'.$q.'%')
                    ->orWhere('customer_id', ctype_digit($q) ? (int) $q : 0)
                    ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', '%'.$q.'%'));
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($type = $request->string('badge_type')->toString()) {
            $query->where('badge_type', $type);
        }

        $rows = $query->paginate(25)->withQueryString();

        return view('trust-verification::admin.verification-badges.index', [
            'rows' => $rows,
            'filters' => $request->only(['q', 'status', 'badge_type']),
            'statuses' => [
                TvVerificationBadge::STATUS_PENDING,
                TvVerificationBadge::STATUS_VERIFIED,
                TvVerificationBadge::STATUS_EXPIRED,
                TvVerificationBadge::STATUS_REVOKED,
            ],
        ]);
    }

    public function show(TvVerificationBadge $verificationBadge)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_READ)) {
            return $denied;
        }

        $verificationBadge->load('order.package', 'order.checkItems');
        $order = $verificationBadge->order;
        $blockers = $order ? TrustVerificationIssuedBadgeService::eligibilityBlockers($order) : [];

        return view('trust-verification::admin.verification-badges.show', [
            'badge' => $verificationBadge,
            'order' => $order,
            'blockers' => $blockers,
            'formatted' => TrustVerificationIssuedBadgeService::formatForCustomer($verificationBadge),
        ]);
    }

    public function issue(Request $request, TvOrder $order)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        try {
            $forcePending = $request->boolean('force_pending');
            $forceVerified = $request->boolean('force_verified');
            $badge = $order->order_type === TvVerificationBadge::TYPE_OWNER
                ? TrustVerificationIssuedBadgeService::issueOwnerBadgeForOrder(
                    $order,
                    auth()->id(),
                    $forceVerified && ! $forcePending
                )
                : TrustVerificationIssuedBadgeService::issueForOrder($order, auth()->id(), $forcePending, $forceVerified);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('trust-verification.orders.show', $order)
                ->with('error', $e->getMessage());
        }

        return redirect()->route('trust-verification.verification-badges.show', $badge)
            ->with('success', 'Badge '.$badge->badge_number.' created.');
    }

    public function issueOwner(TvOrder $order)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        try {
            $badge = TrustVerificationIssuedBadgeService::issueOwnerBadgeForOrder($order, auth()->id(), false);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('trust-verification.orders.show', $order)
                ->with('error', $e->getMessage());
        }

        return redirect()->route('trust-verification.verification-badges.show', $badge)
            ->with('success', 'Owner badge '.$badge->badge_number.' issued (pending until eligible).');
    }

    public function issueVerifiedOwner(TvOrder $order)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        try {
            $badge = TrustVerificationIssuedBadgeService::issueOwnerBadgeForOrder($order, auth()->id(), true);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('trust-verification.orders.show', $order)
                ->with('error', $e->getMessage());
        }

        return redirect()->route('trust-verification.verification-badges.show', $badge)
            ->with('success', 'Verified owner badge '.$badge->badge_number.' issued.');
    }

    public function verify(TvVerificationBadge $verificationBadge)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        TrustVerificationIssuedBadgeService::markVerified($verificationBadge, auth()->id(), 'admin_verify');

        return redirect()->route('trust-verification.verification-badges.show', $verificationBadge)
            ->with('success', 'Badge marked verified.');
    }

    public function revoke(Request $request, TvVerificationBadge $verificationBadge)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        $data = $request->validate([
            'revoke_reason' => 'required|string|max:500',
        ]);

        TrustVerificationIssuedBadgeService::revoke($verificationBadge, $data['revoke_reason'], auth()->id());

        return redirect()->route('trust-verification.verification-badges.show', $verificationBadge)
            ->with('success', 'Badge revoked.');
    }

    public function renew(TvVerificationBadge $verificationBadge)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        TrustVerificationIssuedBadgeService::renew($verificationBadge, auth()->id());

        return redirect()->route('trust-verification.verification-badges.show', $verificationBadge)
            ->with('success', 'Badge renewed.');
    }

    public function downloadCertificate(TvVerificationBadge $verificationBadge)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_READ)) {
            return $denied;
        }

        return TrustVerificationIssuedBadgeService::certificateDownloadResponse($verificationBadge);
    }
}

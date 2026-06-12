<?php

namespace App\Plugins\RentalAgreement\Http\Controllers\Admin;

use App\Plugins\AreaManagement\Models\AgreementRequest;
use App\Plugins\AreaManagement\Models\AgreementRequestDocument;
use App\Plugins\AreaManagement\Services\AgreementRequestKycActivationService;
use App\Plugins\AreaManagement\Services\AgreementRequestService;
use App\Plugins\RentalAgreement\Models\RentalAgreement;
use App\Plugins\RentalAgreement\Services\LifecycleHandoffService;
use App\Plugins\RentalAgreement\Services\TrustVerificationBridgeService;
use App\Plugins\RentalAgreement\Support\ChecksRentalAgreementPermissions;
use App\Plugins\RentalAgreement\Services\RentalAgreementPermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgreementRequestAdminController extends Controller
{
    use ChecksRentalAgreementPermissions;

    public function __construct(
        private readonly AgreementRequestService $service,
        private readonly TrustVerificationBridgeService $tvBridge,
    ) {}

    public function index(): View
    {
        $this->raDenyUnless(RentalAgreementPermissionService::ROLE_CREATE);

        $requests = collect();
        $pendingCount = 0;

        if (Schema::hasTable('agreement_requests')) {
            try {
                $requests = AgreementRequest::query()
                    ->with(['agent:id,name', 'property:id,title,address,city', 'documents'])
                    ->orderByRaw("FIELD(status, 'pending', 'under_review', 'approved', 'rejected')")
                    ->orderByDesc('id')
                    ->limit(200)
                    ->get();

                $pendingCount = $this->service->pendingCount();
            } catch (\Throwable $e) {
                Log::warning('[AgreementRequest] index failed', ['error' => $e->getMessage()]);
            }
        }

        return view('rental-agreement::admin.agreement-requests.index', compact('requests', 'pendingCount'));
    }

    /** Guided processing screen (replaces jump-to-create-only flow). */
    public function show(AgreementRequest $agreement_request): View|RedirectResponse
    {
        $this->raDenyUnless(RentalAgreementPermissionService::ROLE_READ);

        $request = $agreement_request->load([
            'agent:id,name',
            'property:id,title,address,city',
            'documents',
            'tenant:id,name',
            'owner:id,name',
        ]);

        $isOpen = in_array($request->status, [
            AgreementRequest::STATUS_PENDING,
            AgreementRequest::STATUS_UNDER_REVIEW,
        ], true);

        if ($isOpen && $request->status === AgreementRequest::STATUS_PENDING) {
            try {
                $this->service->markUnderReview($request, (int) auth()->id());
                $request->refresh();
            } catch (\Throwable $e) {
                Log::warning('[AgreementRequest] markUnderReview failed', ['id' => $request->id, 'error' => $e->getMessage()]);
            }
        }

        $checklist = [];
        $tvStatus  = ['tenant' => ['summary' => __('Not verified'), 'verified' => false], 'owner' => ['summary' => __('Not verified'), 'verified' => false]];
        $nextHandoff = null;

        try {
            $tvStatus = $this->tvBridge->forAgreementRequest($request);
            $checklist = $this->service->processingChecklist($request, $tvStatus);
        } catch (\Throwable $e) {
            Log::warning('[AgreementRequest] checklist failed', ['id' => $request->id, 'error' => $e->getMessage()]);
        }

        if ($request->created_agreement_id) {
            try {
                $agreement = RentalAgreement::query()->find($request->created_agreement_id);
                if ($agreement) {
                    $nextHandoff = app(LifecycleHandoffService::class)->agreementToMoveInCard($agreement);
                }
            } catch (\Throwable $e) {
                Log::warning('[AgreementRequest] handoff failed', ['id' => $request->id, 'error' => $e->getMessage()]);
            }
        }

        return view('rental-agreement::admin.agreement-requests.show', [
            'request'     => $request,
            'checklist'   => $checklist,
            'tvStatus'    => $tvStatus,
            'nextHandoff' => $nextHandoff,
            'isOpen'      => $isOpen,
        ]);
    }

    public function createAgreement(AgreementRequest $agreement_request): RedirectResponse
    {
        $this->raDenyUnless(RentalAgreementPermissionService::ROLE_CREATE);

        if (! in_array($agreement_request->status, [
            AgreementRequest::STATUS_PENDING,
            AgreementRequest::STATUS_UNDER_REVIEW,
        ], true)) {
            return redirect()->route('admin.agreement-requests.show', $agreement_request)
                ->with('error', __('This request is no longer open for agreement creation.'));
        }

        try {
            if ($agreement_request->status === AgreementRequest::STATUS_PENDING) {
                $this->service->markUnderReview($agreement_request, (int) auth()->id());
            }
        } catch (\Throwable $e) {
            Log::warning('[AgreementRequest] markUnderReview failed', ['error' => $e->getMessage()]);
        }

        return redirect()->route('admin.rental-agreements.create', [
            'agreement_request_id' => $agreement_request->id,
        ]);
    }

    public function updateVerification(Request $httpRequest, AgreementRequest $agreement_request): RedirectResponse
    {
        $this->raDenyUnless(RentalAgreementPermissionService::ROLE_UPDATE);

        if (! in_array($agreement_request->status, [
            AgreementRequest::STATUS_PENDING,
            AgreementRequest::STATUS_UNDER_REVIEW,
        ], true)) {
            return redirect()->route('admin.agreement-requests.show', $agreement_request)
                ->with('error', __('This request is closed.'));
        }

        $data = $httpRequest->validate([
            'verification_handled' => 'nullable|boolean',
        ]);

        try {
            $this->service->updateVerificationHandled(
                $agreement_request,
                (bool) ($data['verification_handled'] ?? false)
            );
        } catch (\Throwable $e) {
            return redirect()->route('admin.agreement-requests.show', $agreement_request)
                ->with('error', __('Could not save verification status.'));
        }

        return redirect()->route('admin.agreement-requests.show', $agreement_request)
            ->with('success', __('Verification status saved.'));
    }

    public function uploadDocument(Request $httpRequest, AgreementRequest $agreement_request): RedirectResponse
    {
        $this->raDenyUnless(RentalAgreementPermissionService::ROLE_UPDATE);

        if (! in_array($agreement_request->status, [
            AgreementRequest::STATUS_PENDING,
            AgreementRequest::STATUS_UNDER_REVIEW,
        ], true)) {
            return redirect()->route('admin.agreement-requests.show', $agreement_request)
                ->with('error', __('This request can no longer accept documents.'));
        }

        $data = $httpRequest->validate([
            'party' => 'nullable|in:tenant,owner',
            'doc_type' => 'required|string|in:aadhaar,pan,photo,electricity_bill,water_bill,id_proof,id_front,id_back,address_proof,other',
            'file'     => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png',
        ]);

        try {
            $result = $this->service->addDocument(
                $agreement_request,
                $httpRequest->file('file'),
                (string) $data['doc_type'],
                AgreementRequestService::ROLE_ADMIN,
                null,
                $httpRequest->input('party', 'tenant'),
            );
        } catch (\Throwable $e) {
            return redirect()->route('admin.agreement-requests.show', $agreement_request)
                ->with('error', __('Upload failed.'));
        }

        if (! ($result['ok'] ?? false)) {
            return redirect()->route('admin.agreement-requests.show', $agreement_request)
                ->with('error', $result['message'] ?? __('Upload failed.'));
        }

        return redirect()->route('admin.agreement-requests.show', $agreement_request)
            ->with('success', __('Document uploaded.'));
    }

    public function reject(Request $httpRequest, AgreementRequest $agreement_request): RedirectResponse
    {
        $this->raDenyUnless(RentalAgreementPermissionService::ROLE_UPDATE);

        if (! in_array($agreement_request->status, [
            AgreementRequest::STATUS_PENDING,
            AgreementRequest::STATUS_UNDER_REVIEW,
        ], true)) {
            return redirect()->route('admin.agreement-requests.index')
                ->with('error', __('This request is no longer open.'));
        }

        $data = $httpRequest->validate([
            'admin_note' => 'required|string|max:2000',
        ]);

        try {
            $this->service->markRejected($agreement_request, $data['admin_note'], (int) auth()->id());
        } catch (\Throwable $e) {
            return redirect()->route('admin.agreement-requests.show', $agreement_request)
                ->with('error', __('Could not reject this request.'));
        }

        return redirect()->route('admin.agreement-requests.index')
            ->with('success', __('Agreement request rejected.'));
    }

    public function downloadDocument(
        AgreementRequest $agreement_request,
        AgreementRequestDocument $document
    ): StreamedResponse {
        $this->raDenyUnless(RentalAgreementPermissionService::ROLE_READ);

        if ((int) $document->agreement_request_id !== (int) $agreement_request->id) {
            abort(404);
        }

        if (! Storage::exists($document->file_path)) {
            abort(404);
        }

        return Storage::download($document->file_path, $document->file_name);
    }

    public function activateKycOnly(AgreementRequest $agreement_request): RedirectResponse
    {
        $this->raDenyUnless(RentalAgreementPermissionService::ROLE_CREATE);

        if (! $agreement_request->isKycOnly()) {
            return redirect()->route('admin.agreement-requests.show', $agreement_request)
                ->with('error', __('This request is not a KYC-only flow.'));
        }

        try {
            $result = app(AgreementRequestKycActivationService::class)
                ->activateFromKycOnlyRequest($agreement_request, (int) auth()->id());
        } catch (\Throwable $e) {
            Log::warning('[AgreementRequest] activateKycOnly failed', ['error' => $e->getMessage()]);

            return redirect()->route('admin.agreement-requests.show', $agreement_request)
                ->with('error', __('Could not activate tenancy.'));
        }

        if (! ($result['ok'] ?? false)) {
            return redirect()->route('admin.agreement-requests.show', $agreement_request)
                ->with('error', $result['message'] ?? __('Could not activate tenancy.'));
        }

        $tenancyId = (int) ($result['tenancy_id'] ?? 0);
        $msg = (string) ($result['message'] ?? __('KYC completed and tenancy activated.'));

        if ($tenancyId && \Illuminate\Support\Facades\Route::has('admin.owner-tenancies.index')) {
            return redirect()->route('admin.agreement-requests.show', $agreement_request)
                ->with('success', $msg . ' ' . __('Tenancy #:id', ['id' => $tenancyId]));
        }

        return redirect()->route('admin.agreement-requests.show', $agreement_request)
            ->with('success', $msg);
    }
}

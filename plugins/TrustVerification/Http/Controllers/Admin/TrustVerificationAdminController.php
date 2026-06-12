<?php

namespace App\Plugins\TrustVerification\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\TrustVerification\Models\TvCheckItem;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvOrderDocument;
use App\Plugins\TrustVerification\Services\TrustVerificationAuditLogService;
use App\Plugins\TrustVerification\Services\TrustVerificationAutomationService;
use App\Plugins\TrustVerification\Services\TrustVerificationDocumentService;
use App\Plugins\TrustVerification\Services\TrustVerificationNotificationService;
use App\Plugins\TrustVerification\Services\TrustVerificationOrderTimestampsService;
use App\Plugins\TrustVerification\Models\TvRiskProfile;
use App\Plugins\TrustVerification\Models\TvRiskSignal;
use App\Plugins\TrustVerification\Services\TrustVerificationRiskService;
use App\Plugins\TrustVerification\Services\TrustVerificationPermissionService;
use App\Plugins\TrustVerification\Services\TrustVerificationPiiRetentionService;
use App\Plugins\TrustVerification\Models\TvPoliceVerification;
use App\Plugins\TrustVerification\Models\TvReferenceContact;
use App\Plugins\TrustVerification\Services\TrustVerificationPoliceDocumentService;
use App\Plugins\TrustVerification\Services\TrustVerificationPoliceVerificationService;
use App\Plugins\TrustVerification\Services\TrustVerificationReferenceService;
use App\Plugins\TrustVerification\Services\TrustVerificationService;
use App\Plugins\TrustVerification\Models\TvVerificationBadge;
use App\Plugins\TrustVerification\Services\TrustVerificationIssuedBadgeService;
use App\Plugins\TrustVerification\Services\TrustVerificationTrustBadgeService;
use App\Plugins\TrustVerification\Support\ChecksTrustVerificationPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TrustVerificationAdminController extends Controller
{
    use ChecksTrustVerificationPermissions;

    public function index(Request $request)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_READ)) {
            return $denied;
        }

        $citySlug = $request->query('city_slug');
        $overdue = $request->boolean('overdue');

        if ($overdue) {
            $query = TrustVerificationService::overdueOrdersQuery($citySlug ?: null);
        } else {
            $query = TvOrder::query()
                ->whereIn('city_slug', TrustVerificationService::registeredCitySlugs());

            if ($citySlug) {
                $query->where('city_slug', $citySlug);
            }
        }

        $this->applyOrderFilters($query, $request, $overdue);

        $orders = $query
            ->with(['package', 'subject', 'report', 'checkItems'])
            ->when($overdue, fn ($q) => $q->orderByDesc('tv_orders.id'), fn ($q) => $q->latest('id'))
            ->paginate(25)
            ->withQueryString();

        return view('trust-verification::admin.index', [
            'orders' => $orders,
            'filters' => $request->only([
                'status', 'order_type', 'payment_status', 'city_slug', 'overdue',
                'q', 'date_from', 'date_to', 'has_report', 'preset',
            ]),
            'statusOptions' => ['submitted', 'in_progress', 'completed', 'cancelled'],
            'paymentOptions' => ['pending', 'paid', 'waived'],
            'cityOptions' => TrustVerificationService::cityCatalog(),
            'opsStats' => TrustVerificationService::opsStats($citySlug ?: null),
        ]);
    }

    private function applyOrderFilters(\Illuminate\Database\Eloquent\Builder $query, Request $request, bool $overdue): void
    {
        $col = fn (string $field) => $overdue ? "tv_orders.{$field}" : $field;

        if ($request->filled('preset')) {
            match ($request->preset) {
                'work_queue' => $query->where($col('payment_status'), 'paid')
                    ->whereIn($col('status'), ['submitted', 'in_progress']),
                'needs_report' => $query->whereIn($col('status'), ['in_progress', 'completed'])
                    ->whereDoesntHave('report', fn ($r) => $r->whereNotNull('file_path')),
                'cancelled' => $query->where($col('status'), 'cancelled'),
                default => null,
            };
        }

        if ($request->filled('status')) {
            $query->where($col('status'), $request->status);
        }
        if ($request->filled('order_type')) {
            $query->where($col('order_type'), $request->order_type);
        }
        if ($request->filled('payment_status')) {
            $query->where($col('payment_status'), $request->payment_status);
        }

        if ($request->query('has_report') === '1') {
            $query->whereHas('report', fn ($r) => $r->whereNotNull('file_path'));
        } elseif ($request->query('has_report') === '0') {
            $query->where(function ($q) {
                $q->whereDoesntHave('report')
                    ->orWhereHas('report', fn ($r) => $r->whereNull('file_path'));
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate($col('created_at'), '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate($col('created_at'), '<=', $request->date_to);
        }

        if ($request->filled('q')) {
            $term = '%'.addcslashes(trim($request->q), '%_\\').'%';
            $query->where(function ($outer) use ($term, $overdue) {
                if ($overdue) {
                    $outer->where('tv_orders.order_number', 'like', $term)
                        ->orWhere('tv_orders.requester_name', 'like', $term)
                        ->orWhere('tv_orders.requester_phone', 'like', $term)
                        ->orWhere('tv_orders.requester_email', 'like', $term)
                        ->orWhereExists(function ($sub) use ($term) {
                            $sub->selectRaw('1')
                                ->from('tv_subjects')
                                ->whereColumn('tv_subjects.order_id', 'tv_orders.id')
                                ->where(function ($s) use ($term) {
                                    $s->where('full_name', 'like', $term)
                                        ->orWhere('phone', 'like', $term);
                                });
                        });
                } else {
                    $outer->where('order_number', 'like', $term)
                        ->orWhere('requester_name', 'like', $term)
                        ->orWhere('requester_phone', 'like', $term)
                        ->orWhere('requester_email', 'like', $term)
                        ->orWhereHas('subject', function ($s) use ($term) {
                            $s->where('full_name', 'like', $term)
                                ->orWhere('phone', 'like', $term);
                        });
                }
            });
        }
    }

    public function show(Request $request, TvOrder $order)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_READ)) {
            return $denied;
        }

        $order->load(['package', 'subject', 'checkItems', 'report', 'documents', 'automationRuns', 'auditLogs', 'referenceContacts', 'policeVerification']);

        TrustVerificationAuditLogService::logAdmin(
            $request,
            $order,
            TrustVerificationAuditLogService::ACTION_ADMIN_VIEWED_ORDER,
            'Viewed order detail page'
        );

        $checkSummary = [
            'total' => $order->checkItems->count(),
            'pass' => $order->checkItems->where('status', 'pass')->count(),
            'fail' => $order->checkItems->where('status', 'fail')->count(),
            'pending' => $order->checkItems->where('status', 'pending')->count(),
            'na' => $order->checkItems->where('status', 'na')->count(),
        ];

        $dueAt = $order->package && $order->created_at
            ? $order->created_at->copy()->addHours($order->package->delivery_hours)
            : null;

        $riskSuggestion = TrustVerificationRiskService::suggestRiskLevel($order);

        $riskProfile = $order->customer_id
            ? TvRiskProfile::where('customer_id', $order->customer_id)->first()
            : null;
        $riskSignals = $order->customer_id
            ? TvRiskSignal::query()
                ->where('customer_id', $order->customer_id)
                ->where(function ($q) use ($order) {
                    $q->where('order_id', $order->id)->orWhereNull('order_id');
                })
                ->orderByDesc('id')
                ->limit(20)
                ->get()
            : collect();

        return view('trust-verification::admin.show', [
            'order' => $order,
            'formatted' => TrustVerificationService::formatOrder($order, true),
            'orderTimestamps' => TrustVerificationOrderTimestampsService::resolve($order),
            'checkStatuses' => ['pending', 'pass', 'fail', 'na'],
            'riskLevels' => ['green', 'amber', 'red'],
            'checkSummary' => $checkSummary,
            'dueAt' => $dueAt,
            'cityLabel' => TrustVerificationService::cityLabel($order->city_slug),
            'hasReportFile' => TrustVerificationService::reportFileExists($order->report),
            'reportMissingForCompleted' => $order->status === 'completed'
                && in_array($order->payment_status, ['paid', 'waived'], true)
                && ! TrustVerificationService::reportFileExists($order->report),
            'hasActiveDocuments' => $order->documents->contains(
                fn (TvOrderDocument $doc) => ! $doc->deleted_at
            ),
            'riskSuggestion' => $riskSuggestion,
            'riskProfile' => $riskProfile,
            'riskSignals' => $riskSignals,
            'riskSummaryDraft' => TrustVerificationAutomationService::generateRiskSummary($order),
            'automationSettings' => \App\Plugins\TrustVerification\Services\TrustVerificationSettingsService::automationPayload(),
            'documentTypes' => \App\Plugins\TrustVerification\Services\TrustVerificationSettingsService::DOCUMENT_TYPES,
            'auditLogs' => TrustVerificationPermissionService::can(TrustVerificationPermissionService::ROLE_AUDIT)
                ? $order->auditLogs()->latest('id')->limit(200)->get()
                : collect(),
            'referenceTypes' => TrustVerificationReferenceService::TYPE_LABELS,
            'referenceStatuses' => TrustVerificationReferenceService::STATUS_LABELS,
            'referenceProgress' => TrustVerificationReferenceService::progressForOrder($order),
            'hasReferenceCheck' => TrustVerificationReferenceService::orderHasReferenceCheck($order),
            'hasPoliceVerification' => TrustVerificationPoliceVerificationService::orderRequiresPoliceVerification($order),
            'policeStatuses' => TrustVerificationPoliceVerificationService::STATUS_LABELS,
            'policeVerificationTypes' => TrustVerificationPoliceVerificationService::VERIFICATION_TYPES,
            'rajasthanStatusUrl' => TrustVerificationPoliceVerificationService::RAJASTHAN_STATUS_CHECK_URL,
            'verificationBadge' => TvVerificationBadge::where('order_id', $order->id)->first(),
            'verificationBadgeBlockers' => TrustVerificationIssuedBadgeService::eligibilityBlockers($order),
        ]);
    }

    public function updateStatus(Request $request, TvOrder $order)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        $data = $request->validate([
            'status' => 'required|in:submitted,in_progress,completed,cancelled',
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        $previousStatus = $order->status;
        $order->status = $data['status'];
        $order->admin_notes = $data['admin_notes'] ?? $order->admin_notes;
        if ($data['status'] === 'completed' && ! $order->completed_at) {
            $order->completed_at = now();
        }
        $order->save();

        TrustVerificationAuditLogService::logAdmin(
            $request,
            $order,
            TrustVerificationAuditLogService::ACTION_ADMIN_CHANGED_ORDER_STATUS,
            'Order status changed from '.$previousStatus.' to '.$order->status,
            ['from' => $previousStatus, 'to' => $order->status]
        );

        if ($order->status === 'completed') {
            TrustVerificationTrustBadgeService::onOrderEvent($order->fresh(), 'order_completed');
        }

        return redirect()->route('trust-verification.orders.show', $order)->with('success', 'Order status updated');
    }

    public function markPayment(Request $request, TvOrder $order)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_PAYMENTS)) {
            return $denied;
        }

        $data = $request->validate([
            'payment_status' => 'required|in:pending,paid,waived',
        ]);

        $previousPayment = $order->payment_status;
        $order->payment_status = $data['payment_status'];
        $order->save();

        TrustVerificationAuditLogService::logAdmin(
            $request,
            $order,
            TrustVerificationAuditLogService::ACTION_ADMIN_CHANGED_PAYMENT_STATUS,
            'Payment status changed from '.$previousPayment.' to '.$order->payment_status,
            ['from' => $previousPayment, 'to' => $order->payment_status]
        );

        if (in_array($data['payment_status'], ['paid', 'waived'], true)) {
            TrustVerificationAutomationService::maybeRunOnPaid($order->fresh());
            TrustVerificationTrustBadgeService::onOrderEvent($order->fresh(), 'payment_updated');
        }

        return redirect()->route('trust-verification.orders.show', $order)->with('success', 'Payment status updated');
    }

    public function updateChecks(Request $request, TvOrder $order)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        $payload = $request->input('checks', []);
        foreach ($payload as $checkId => $row) {
            $item = TvCheckItem::where('order_id', $order->id)->where('id', $checkId)->first();
            if (! $item) {
                continue;
            }
            $status = $row['status'] ?? $item->status;
            if (! in_array($status, ['pending', 'pass', 'fail', 'na'], true)) {
                continue;
            }
            $item->status = $status;
            $item->notes = $row['notes'] ?? $item->notes;
            $item->completed_at = in_array($status, ['pass', 'fail'], true) ? now() : null;
            $item->save();
        }

        if ($order->status === 'submitted') {
            $order->update(['status' => 'in_progress']);
        }

        TrustVerificationTrustBadgeService::onOrderEvent($order->fresh(), 'checks_updated');

        return redirect()->route('trust-verification.orders.show', $order)->with('success', 'Checks updated');
    }

    public function uploadReport(Request $request, TvOrder $order)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_REPORTS)) {
            return $denied;
        }

        $validator = Validator::make($request->all(), [
            'report_file' => 'nullable|file|mimes:pdf|max:10240',
            'risk_level' => 'nullable|in:green,amber,red',
            'summary' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $riskLevel = $request->input('risk_level');
        if (empty($riskLevel)) {
            $riskLevel = TrustVerificationRiskService::suggestRiskLevel($order->fresh(['checkItems']))['level'];
        }

        $summary = $request->input('summary');
        if (empty(trim((string) $summary))) {
            $summary = TrustVerificationAutomationService::generateRiskSummary($order->fresh(['checkItems', 'subject']));
        }

        if ($request->hasFile('report_file')) {
            try {
                $report = TrustVerificationService::storeReportFile(
                    $order,
                    $request->file('report_file'),
                    auth()->id(),
                    $riskLevel,
                    $summary
                );
            } catch (\Throwable $e) {
                return redirect()->back()->withErrors(['report_file' => $e->getMessage()])->withInput();
            }
            TrustVerificationAuditLogService::logAdmin(
                $request,
                $order,
                TrustVerificationAuditLogService::ACTION_ADMIN_UPLOADED_REPORT,
                'Uploaded verification report PDF',
                ['report_id' => $report->id, 'risk_level' => $riskLevel]
            );
            $reportUrl = TrustVerificationService::signedEmailReportUrl($order->fresh(['report']));
            TrustVerificationNotificationService::reportReady($order->fresh(), $reportUrl);
        } else {
            $report = $order->report;
            if ($report) {
                $report->risk_level = $riskLevel;
                $report->summary = $summary;
                $report->save();
            }
        }

        return redirect()->route('trust-verification.orders.show', $order)->with('success', 'Report saved');
    }

    public function runAutomation(Request $request, TvOrder $order)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        try {
            TrustVerificationAutomationService::runForOrder($order, 'admin_manual');
            TrustVerificationAuditLogService::logAdmin(
                $request,
                $order,
                TrustVerificationAuditLogService::ACTION_ADMIN_RAN_AUTOMATION,
                'Ran manual automation'
            );
        } catch (\Throwable $e) {
            return redirect()->route('trust-verification.orders.show', $order)
                ->with('error', 'Automation failed: '.$e->getMessage());
        }

        return redirect()->route('trust-verification.orders.show', $order)->with('success', 'Automation run completed');
    }

    public function downloadDocument(Request $request, TvOrder $order, TvOrderDocument $document)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_DOCUMENTS)) {
            return $denied;
        }

        if ((int) $document->order_id !== (int) $order->id) {
            abort(404);
        }

        if (! TrustVerificationDocumentService::fileExists($document)) {
            TrustVerificationAuditLogService::logAdmin(
                $request,
                $order,
                TrustVerificationAuditLogService::ACTION_ADMIN_DOCUMENT_DOWNLOAD_BLOCKED,
                'Document download blocked: file missing or removed',
                ['document_id' => $document->id, 'doc_type' => $document->doc_type]
            );
            abort(404, 'Document not found or has been removed');
        }

        TrustVerificationAuditLogService::logAdminDownloadedDocument($request, $order, $document);

        return TrustVerificationDocumentService::downloadResponse($document);
    }

    public function downloadReport(Request $request, TvOrder $order)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_REPORTS)) {
            return $denied;
        }

        TrustVerificationAuditLogService::logAdmin(
            $request,
            $order,
            TrustVerificationAuditLogService::ACTION_ADMIN_DOWNLOADED_REPORT,
            'Downloaded verification report'
        );

        return TrustVerificationService::downloadReportResponse($order);
    }

    public function deleteDocuments(Request $request, TvOrder $order)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_DOCUMENTS)) {
            return $denied;
        }

        $result = TrustVerificationPiiRetentionService::purgeOrderDocuments(
            $order,
            TrustVerificationPiiRetentionService::DELETED_BY_ADMIN,
            auth()->id(),
            'Admin deleted documents from order detail',
            false
        );

        if ($result['count'] === 0) {
            return redirect()->route('trust-verification.orders.show', $order)
                ->with('error', 'No document files to delete');
        }

        TrustVerificationAuditLogService::logAdmin(
            $request,
            $order,
            TrustVerificationAuditLogService::ACTION_ADMIN_DELETED_DOCUMENTS,
            'Admin deleted '.$result['count'].' document file(s)',
            ['count' => $result['count'], 'paths' => $result['paths']]
        );

        return redirect()->route('trust-verification.orders.show', $order)
            ->with('success', 'Document files deleted. Order record kept.');
    }

    public function deleteReport(Request $request, TvOrder $order)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_REPORTS)) {
            return $denied;
        }

        $order->loadMissing('report');
        if (! $order->report) {
            return redirect()->route('trust-verification.orders.show', $order)
                ->with('error', 'No report on this order');
        }

        if (! TrustVerificationPiiRetentionService::purgeReport(
            $order->report,
            TrustVerificationPiiRetentionService::DELETED_BY_ADMIN,
            auth()->id(),
            'Admin deleted report from order detail',
            false
        )) {
            return redirect()->route('trust-verification.orders.show', $order)
                ->with('error', 'Report file already removed');
        }

        TrustVerificationAuditLogService::logAdmin(
            $request,
            $order,
            TrustVerificationAuditLogService::ACTION_ADMIN_DELETED_REPORT,
            'Admin deleted verification report PDF',
            ['report_id' => $order->report->id, 'path' => $order->report->file_path]
        );

        return redirect()->route('trust-verification.orders.show', $order)
            ->with('success', 'Report file deleted. Order and payment records kept.');
    }

    public function storeReference(Request $request, TvOrder $order)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        if (! TrustVerificationReferenceService::orderHasReferenceCheck($order)) {
            return redirect()->route('trust-verification.orders.show', $order)
                ->with('error', 'This order does not include reference check.');
        }

        $request->validate([
            'reference_type' => 'required|in:'.implode(',', array_keys(TrustVerificationReferenceService::TYPE_LABELS)),
            'name' => 'required|string|max:120',
            'relation' => 'nullable|string|max:80',
            'mobile' => 'required|string|max:20',
            'email' => 'nullable|email|max:190',
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $validated = TrustVerificationReferenceService::validateCustomerRows([
                $request->only([
                    'reference_type', 'name', 'relation', 'mobile', 'email', 'notes',
                ]),
            ]);
            $sort = (int) $order->referenceContacts()->max('sort_order');
            $row = $validated[0];
            $contact = $order->referenceContacts()->create([
                'reference_type' => $row['reference_type'],
                'name' => $row['name'],
                'relation' => $row['relation'] ?? null,
                'mobile' => $row['mobile'],
                'email' => $row['email'] ?? null,
                'notes' => $row['notes'] ?? null,
                'status' => 'pending',
                'last_status_at' => now(),
                'sort_order' => $sort + 1,
            ]);

            TrustVerificationReferenceService::syncReferenceCheckItem(
                $order->fresh(['checkItems', 'referenceContacts'])
            );

            TrustVerificationAuditLogService::logAdmin(
                $request,
                $order,
                TrustVerificationAuditLogService::ACTION_ADMIN_CREATED_REFERENCE,
                'Admin added reference contact #'.$contact->id,
                ['reference_id' => $contact->id]
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('trust-verification.orders.show', $order)
                ->with('error', $e->getMessage());
        }

        return redirect()->route('trust-verification.orders.show', $order)
            ->with('success', 'Reference contact added.');
    }

    public function updateReference(Request $request, TvOrder $order, TvReferenceContact $reference)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        if ((int) $reference->order_id !== (int) $order->id) {
            abort(404);
        }

        try {
            TrustVerificationReferenceService::updateFromAdmin(
                $reference,
                $request->all(),
                $request
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('trust-verification.orders.show', $order)
                ->with('error', $e->getMessage());
        }

        return redirect()->route('trust-verification.orders.show', $order)
            ->with('success', 'Reference contact updated.');
    }

    public function redirectPoliceVerification(Request $request, TvOrder $order)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_READ)) {
            return $denied;
        }

        return redirect()->to(route('trust-verification.orders.show', $order).'#police-verification');
    }

    public function updatePoliceVerification(Request $request, TvOrder $order)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        if (! TrustVerificationPoliceVerificationService::orderRequiresPoliceVerification($order)) {
            return redirect()->route('trust-verification.orders.show', $order)
                ->with('error', 'This order does not include police verification.');
        }

        try {
            $record = TrustVerificationPoliceVerificationService::getOrCreateForOrder($order);
            TrustVerificationPoliceVerificationService::updateFromAdmin(
                $record,
                $request->except(['_token', 'acknowledgement', 'certificate', 'status_action']),
                $request->file('acknowledgement'),
                $request->file('certificate'),
                $request
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('trust-verification.orders.show', $order)
                ->with('error', $e->getMessage());
        }

        return redirect()->route('trust-verification.orders.show', $order)
            ->with('success', 'Police verification updated.');
    }

    public function transitionPoliceStatus(Request $request, TvOrder $order)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return $denied;
        }

        $request->validate([
            'status' => 'required|in:'.implode(',', TrustVerificationPoliceVerificationService::STATUSES),
        ]);

        try {
            $record = TrustVerificationPoliceVerificationService::getOrCreateForOrder($order);
            TrustVerificationPoliceVerificationService::transitionStatus(
                $record,
                $request->input('status'),
                $request
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('trust-verification.orders.show', $order)
                ->with('error', $e->getMessage());
        }

        return redirect()->route('trust-verification.orders.show', $order)
            ->with('success', 'Police verification status updated.');
    }

    public function downloadPoliceAcknowledgement(Request $request, TvOrder $order)
    {
        if ($denied = $this->tvDenyPoliceDocumentDownload()) {
            return $denied;
        }

        $record = $order->policeVerification;
        if (! $record) {
            abort(404);
        }

        TrustVerificationAuditLogService::logAdmin(
            $request,
            $order,
            TrustVerificationAuditLogService::ACTION_POLICE_ACKNOWLEDGEMENT_DOWNLOADED,
            'Admin downloaded police acknowledgement',
            ['police_verification_id' => $record->id]
        );

        return TrustVerificationPoliceDocumentService::downloadAcknowledgement($record, null, true);
    }

    public function downloadPoliceCertificate(Request $request, TvOrder $order)
    {
        if ($denied = $this->tvDenyPoliceReportDownload()) {
            return $denied;
        }

        $record = $order->policeVerification;
        if (! $record) {
            abort(404);
        }

        TrustVerificationAuditLogService::logAdmin(
            $request,
            $order,
            TrustVerificationAuditLogService::ACTION_POLICE_CERTIFICATE_DOWNLOADED,
            'Admin downloaded police certificate',
            ['police_verification_id' => $record->id]
        );

        return TrustVerificationPoliceDocumentService::downloadCertificate($record, null, true);
    }

    private function tvDenyPoliceDocumentDownload(): ?RedirectResponse
    {
        if (TrustVerificationPermissionService::can(TrustVerificationPermissionService::ROLE_DOCUMENTS)
            || TrustVerificationPermissionService::can(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return null;
        }

        return redirect()->back()->with('error', TrustVerificationPermissionService::DENIED_MESSAGE);
    }

    private function tvDenyPoliceReportDownload(): ?RedirectResponse
    {
        if (TrustVerificationPermissionService::can(TrustVerificationPermissionService::ROLE_REPORTS)
            || TrustVerificationPermissionService::can(TrustVerificationPermissionService::ROLE_UPDATE)) {
            return null;
        }

        return redirect()->back()->with('error', TrustVerificationPermissionService::DENIED_MESSAGE);
    }
}

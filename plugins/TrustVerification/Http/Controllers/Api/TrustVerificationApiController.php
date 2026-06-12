<?php

namespace App\Plugins\TrustVerification\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvOrderDocument;
use App\Plugins\TrustVerification\Models\TvPackage;
use App\Plugins\TrustVerification\Models\TvSampleReport;
use App\Plugins\TrustVerification\Services\TrustVerificationAuditLogService;
use App\Plugins\TrustVerification\Services\TrustVerificationAutomationService;
use App\Plugins\TrustVerification\Services\DocumentUploadRejectedException;
use App\Plugins\TrustVerification\Services\TrustVerificationDocumentService;
use App\Plugins\TrustVerification\Services\TrustVerificationNotificationService;
use App\Plugins\TrustVerification\Services\TrustVerificationPaymentService;
use App\Plugins\TrustVerification\Services\TrustVerificationOrderTimestampsService;
use App\Plugins\TrustVerification\Services\TrustVerificationService;
use App\Plugins\TrustVerification\Services\TrustVerificationSettingsService;
use App\Plugins\TrustVerification\Services\TrustVerificationContentService;
use App\Plugins\TrustVerification\Services\TrustVerificationPoliceDocumentService;
use App\Plugins\TrustVerification\Services\TrustVerificationPoliceVerificationService;
use App\Plugins\TrustVerification\Services\TrustVerificationReferenceService;
use App\Plugins\TrustVerification\Services\TrustVerificationSampleReportService;
use App\Plugins\TrustVerification\Services\TrustVerificationFraudRiskService;
use App\Plugins\TrustVerification\Services\TrustVerificationTenantReliabilityService;
use App\Plugins\TrustVerification\Models\TvVerificationBadge;
use App\Plugins\TrustVerification\Services\TrustVerificationIssuedBadgeService;
use App\Plugins\TrustVerification\Services\TrustVerificationTrustBadgeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TrustVerificationApiController extends Controller
{
    public function paymentSettings()
    {
        return response()->json([
            'error' => false,
            'message' => 'Payment settings fetched successfully',
            'data' => array_merge(
                TrustVerificationPaymentService::paymentSettingsPayload(),
                TrustVerificationSettingsService::publicPayload()
            ),
        ]);
    }

    public function documentSettings()
    {
        return response()->json([
            'error' => false,
            'message' => 'Settings fetched successfully',
            'data' => TrustVerificationSettingsService::publicPayload(),
        ]);
    }

    public function publicContent()
    {
        return response()->json([
            'error' => false,
            'message' => 'Content fetched successfully',
            'data' => TrustVerificationContentService::publicPayload(),
        ]);
    }

    public function publicSampleReport(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'type' => 'required|string|in:tenant,owner',
            'city' => 'nullable|string|max:80',
            'package_id' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $payload = TrustVerificationSampleReportService::publicPayload(
            (string) $request->query('type'),
            $request->query('city') ? (string) $request->query('city') : null,
            $request->query('package_id') ? (int) $request->query('package_id') : null
        );

        if (! $payload) {
            return response()->json([
                'error' => false,
                'message' => 'No sample report available for this selection',
                'data' => null,
            ]);
        }

        $hub = TrustVerificationContentService::publicPayload()['hub'] ?? [];

        return response()->json([
            'error' => false,
            'message' => 'Sample report fetched successfully',
            'data' => array_merge($payload, [
                'cms' => [
                    'title' => $hub['sample_report_title'] ?? null,
                    'description' => $hub['sample_report_description'] ?? null,
                    'button_text' => $hub['sample_report_button_text'] ?? null,
                ],
            ]),
        ]);
    }

    public function downloadSampleReport(Request $request, TvSampleReport $sampleReport)
    {
        if (! $sampleReport->is_active || ! TrustVerificationSampleReportService::fileIsReadable($sampleReport)) {
            return response()->json(['error' => true, 'message' => 'Sample report not available'], 404);
        }

        $inline = $request->boolean('inline');
        if (! $inline) {
            TrustVerificationSampleReportService::logDownloaded($request, $sampleReport, [
                'source' => (string) $request->query('source', 'web'),
            ]);
        }

        return TrustVerificationSampleReportService::downloadResponse($sampleReport, $inline);
    }

    public function sampleReportViewed(Request $request, TvSampleReport $sampleReport)
    {
        if (! $sampleReport->is_active || ! TrustVerificationSampleReportService::fileIsReadable($sampleReport)) {
            return response()->json(['error' => true, 'message' => 'Sample report not available'], 404);
        }

        TrustVerificationSampleReportService::logViewed($request, $sampleReport, [
            'source' => (string) $request->input('source', 'web'),
        ]);

        return response()->json([
            'error' => false,
            'message' => 'View recorded',
        ]);
    }

    public function createPaymentIntent(Request $request, TvOrder $order)
    {
        if ((int) $order->customer_id !== (int) $request->user()->id) {
            return response()->json(['error' => true, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'payment_method' => 'nullable|string|in:cashfree',
            'platform_type' => 'nullable|string|in:web,app',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $payload = TrustVerificationPaymentService::createPaymentIntent(
                $order,
                $request->user(),
                $request->input('payment_method', 'cashfree'),
                $request->input('platform_type', 'web')
            );

            return response()->json([
                'error' => false,
                'message' => 'Payment link created',
                'data' => $payload,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function confirmPayment(Request $request, TvOrder $order)
    {
        if ((int) $order->customer_id !== (int) $request->user()->id) {
            return response()->json(['error' => true, 'message' => 'Unauthorized'], 403);
        }

        try {
            $updated = TrustVerificationPaymentService::confirmPayment(
                $order,
                $request->user(),
                $request->integer('payment_transaction_id') ?: null
            );

            return response()->json([
                'error' => false,
                'message' => $updated->payment_status === 'paid'
                    ? 'Payment confirmed'
                    : 'Payment still pending',
                'data' => TrustVerificationService::formatOrder($updated),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function cities()
    {
        $cities = collect(TrustVerificationService::availableCities())
            ->map(function (array $city) {
                return array_merge($city, [
                    'has_tenant_packages' => TrustVerificationService::cityHasPackages($city['slug'], 'tenant'),
                    'has_owner_packages' => TrustVerificationService::cityHasPackages($city['slug'], 'owner'),
                ]);
            })
            ->values();

        return response()->json([
            'error' => false,
            'message' => 'Cities fetched successfully',
            'data' => $cities,
        ]);
    }

    public function packages(Request $request)
    {
        $type = $request->query('type', 'tenant');
        $citySlug = TrustVerificationService::normalizeCitySlug($request->query('city'));

        $packages = TvPackage::query()
            ->where('is_active', true)
            ->where('type', $type)
            ->where('city_slug', $citySlug)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get()
            ->map(fn (TvPackage $p) => TrustVerificationService::formatPackage($p));

        return response()->json([
            'error' => false,
            'message' => 'Packages fetched successfully',
            'data' => $packages,
        ]);
    }

    public function orders(Request $request)
    {
        $customer = $request->user();

        $orders = TvOrder::query()
            ->where('customer_id', $customer->id)
            ->with(['package', 'subject', 'checkItems', 'report', 'documents', 'automationRuns', 'referenceContacts', 'policeVerification'])
            ->latest('id')
            ->paginate(20);

        $collection = $orders->getCollection();
        $timestampMap = TrustVerificationOrderTimestampsService::resolveForOrders($collection);

        return response()->json([
            'error' => false,
            'message' => 'Orders fetched successfully',
            'data' => $collection->map(
                fn (TvOrder $order) => TrustVerificationService::formatOrder(
                    $order,
                    true,
                    $timestampMap[$order->id] ?? null
                )
            )->values(),
            'pagination' => [
                'total' => $orders->total(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    public function showOrder(Request $request, TvOrder $order)
    {
        if ((int) $order->customer_id !== (int) $request->user()->id) {
            return response()->json(['error' => true, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'error' => false,
            'message' => 'Order fetched successfully',
            'data' => TrustVerificationService::formatOrder($order, true),
        ]);
    }

    public function storeOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_id' => 'required|exists:tv_packages,id',
            'city_slug' => 'nullable|string|max:80',
            'requester_name' => 'nullable|string|max:120',
            'requester_email' => 'nullable|email|max:190',
            'requester_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:2000',
            'property_id' => 'nullable|integer',
            'subject.full_name' => 'required|string|max:120',
            'subject.phone' => 'required|string|max:20',
            'subject.email' => 'nullable|email|max:190',
            'subject.current_address' => 'nullable|string|max:500',
            'subject.permanent_address' => 'nullable|string|max:500',
            'subject.property_address' => 'nullable|string|max:500',
            'subject.id_type' => 'nullable|string|max:40',
            'subject.id_number' => 'nullable|string|max:64',
            'subject.employment_company' => 'nullable|string|max:190',
            'subject.employment_role' => 'nullable|string|max:120',
            'subject.consent_given' => 'accepted',
            'references' => 'nullable|array|max:10',
            'references.*.reference_type' => 'required_with:references|in:previous_landlord,employer,family_reference',
            'references.*.name' => 'required_with:references|string|max:120',
            'references.*.relation' => 'nullable|string|max:80',
            'references.*.mobile' => 'required_with:references|string|max:20',
            'references.*.email' => 'nullable|email|max:190',
            'references.*.notes' => 'nullable|string|max:2000',
            'police_verification' => 'nullable|array',
            'police_verification.police_station_name' => 'required_with:police_verification|string|max:160',
            'police_verification.city' => 'required_with:police_verification|string|max:80',
            'police_verification.district' => 'required_with:police_verification|string|max:80',
            'police_verification.applicant_mobile' => 'required_with:police_verification|string|max:20',
            'police_verification.reference_number' => 'nullable|string|max:64',
            'police_verification.customer_notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
                'data' => $validator->errors(),
            ], 422);
        }

        $package = TvPackage::where('id', $request->package_id)
            ->where('is_active', true)
            ->whereIn('city_slug', TrustVerificationService::enabledCitySlugs())
            ->first();

        if (! $package) {
            return response()->json(['error' => true, 'message' => 'Package not available'], 404);
        }

        $payload = $validator->validated();
        $requestedCity = TrustVerificationService::normalizeCitySlug($payload['city_slug'] ?? null);
        if (! empty($payload['city_slug']) && $requestedCity !== $package->city_slug) {
            return response()->json([
                'error' => true,
                'message' => 'Package does not match the selected city',
            ], 422);
        }
        $payload['city_slug'] = $package->city_slug;
        if (! empty($payload['property_id'])) {
            $link = 'Linked property ID: '.$payload['property_id'];
            $payload['notes'] = trim(($payload['notes'] ?? '').($payload['notes'] ? "\n" : '').$link);
        }

        try {
            $order = TrustVerificationService::createOrder(
                $request->user(),
                $package,
                $payload,
                $request
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 422);
        }

        TrustVerificationAuditLogService::logCustomer(
            $request,
            $order,
            TrustVerificationAuditLogService::ACTION_CUSTOMER_CREATED_ORDER,
            'Order '.$order->order_number.' created'
        );

        TrustVerificationFraudRiskService::onOrderEvent($order->fresh(), 'order_created');

        TrustVerificationAutomationService::applyInitialIdValidation($order, $payload['subject'] ?? []);

        if (! empty($payload['references']) && TrustVerificationReferenceService::orderHasReferenceCheck($order)) {
            try {
                TrustVerificationReferenceService::createManyForCustomer(
                    $order,
                    $payload['references'],
                    $request
                );
                $order->refresh()->load(['package', 'subject', 'checkItems', 'report', 'documents', 'referenceContacts', 'policeVerification']);
            } catch (\InvalidArgumentException $e) {
                return response()->json([
                    'error' => true,
                    'message' => $e->getMessage(),
                ], 422);
            }
        }

        if (! empty($payload['police_verification']) && TrustVerificationPoliceVerificationService::orderRequiresPoliceVerification($order)) {
            try {
                TrustVerificationPoliceVerificationService::upsertFromCustomer(
                    $order,
                    $payload['police_verification'],
                    null,
                    $request
                );
                $order->refresh()->load(['package', 'subject', 'checkItems', 'report', 'documents', 'referenceContacts', 'policeVerification']);
            } catch (\InvalidArgumentException $e) {
                return response()->json([
                    'error' => true,
                    'message' => $e->getMessage(),
                ], 422);
            }
        }

        TrustVerificationNotificationService::orderSubmitted($order);

        $message = TrustVerificationPaymentService::isCashfreeActive()
            ? 'Verification request submitted. Complete payment to start processing.'
            : 'Verification request submitted. Our team will contact you for payment and share the report by email.';

        return response()->json([
            'error' => false,
            'message' => $message,
            'data' => TrustVerificationService::formatOrder($order),
        ], 201);
    }

    public function downloadReport(Request $request, TvOrder $order)
    {
        if ((int) $order->customer_id !== (int) $request->user()->id) {
            return response()->json(['error' => true, 'message' => 'Unauthorized'], 403);
        }

        $order->loadMissing('report');

        if (! TrustVerificationService::reportFileExists($order->report)) {
            return response()->json([
                'error' => true,
                'message' => 'Report is not available yet. Please contact support.',
            ], 404);
        }

        TrustVerificationAuditLogService::logCustomer(
            $request,
            $order,
            TrustVerificationAuditLogService::ACTION_CUSTOMER_DOWNLOADED_REPORT,
            'Downloaded verification report'
        );

        return TrustVerificationService::downloadReportResponse($order);
    }

    public function emailDownloadReport(Request $request, TvOrder $order)
    {
        $order->loadMissing('report');

        if (! TrustVerificationService::reportFileExists($order->report)) {
            return response()->json(['error' => true, 'message' => 'Report not found'], 404);
        }

        return TrustVerificationService::downloadReportResponse($order);
    }

    public function cancelOrder(Request $request, TvOrder $order)
    {
        if ((int) $order->customer_id !== (int) $request->user()->id) {
            return response()->json(['error' => true, 'message' => 'Unauthorized'], 403);
        }

        try {
            $updated = TrustVerificationService::cancelOrder($order, $request->user());

            TrustVerificationAuditLogService::logCustomer(
                $request,
                $updated,
                TrustVerificationAuditLogService::ACTION_CUSTOMER_CANCELLED_ORDER,
                'Order '.$updated->order_number.' cancelled by customer'
            );

            return response()->json([
                'error' => false,
                'message' => 'Order cancelled',
                'data' => TrustVerificationService::formatOrder($updated, true),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function uploadDocuments(Request $request, TvOrder $order)
    {
        if ((int) $order->customer_id !== (int) $request->user()->id) {
            return response()->json(['error' => true, 'message' => 'Unauthorized'], 403);
        }

        if (! in_array($order->status, ['submitted', 'in_progress'], true)) {
            return response()->json(['error' => true, 'message' => 'Documents cannot be uploaded for this order'], 422);
        }

        $validator = Validator::make($request->all(), [
            'doc_type' => 'required|string|in:'.implode(',', array_keys(TrustVerificationSettingsService::DOCUMENT_TYPES)),
            'file' => 'required|file|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $doc = TrustVerificationDocumentService::store(
                $order,
                $request->input('doc_type'),
                $request->file('file'),
                $request->user()
            );

            TrustVerificationAuditLogService::logCustomer(
                $request,
                $order,
                TrustVerificationAuditLogService::ACTION_CUSTOMER_UPLOAD_ACCEPTED,
                'Uploaded document: '.$request->input('doc_type'),
                ['document_id' => $doc->id, 'doc_type' => $doc->doc_type]
            );

            return response()->json([
                'error' => false,
                'message' => 'Document uploaded',
                'data' => TrustVerificationDocumentService::formatDocument($doc),
            ]);
        } catch (DocumentUploadRejectedException $e) {
            TrustVerificationAuditLogService::logCustomer(
                $request,
                $order,
                TrustVerificationAuditLogService::ACTION_CUSTOMER_UPLOAD_REJECTED,
                'Upload rejected: '.$e->getMessage(),
                ['doc_type' => $request->input('doc_type'), 'reason' => $e->reasonCode]
            );

            TrustVerificationFraudRiskService::onOrderEvent($order->fresh(), 'document_rejected');

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function downloadDocument(Request $request, TvOrder $order, TvOrderDocument $document)
    {
        if ((int) $order->customer_id !== (int) $request->user()->id) {
            return response()->json(['error' => true, 'message' => 'Unauthorized'], 403);
        }

        if ((int) $document->order_id !== (int) $order->id) {
            return response()->json(['error' => true, 'message' => 'Not found'], 404);
        }

        return TrustVerificationDocumentService::downloadResponse($document);
    }

    public function automationWebhook(Request $request)
    {
        $secret = trim((string) TrustVerificationSettingsService::get('webhook_secret', ''));
        $provided = trim((string) ($request->header('X-TV-Webhook-Secret') ?: $request->input('secret', '')));

        if ($secret === '' || ! hash_equals($secret, $provided)) {
            return response()->json(['error' => true, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'order_number' => 'required|string|max:32',
            'results' => 'required|array|min:1',
            'results.*.check_key' => 'required|string|max:60',
            'results.*.status' => 'required|string|in:pending,pass,fail,na',
            'results.*.notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $order = TvOrder::where('order_number', $request->input('order_number'))->first();
        if (! $order) {
            return response()->json(['error' => true, 'message' => 'Order not found'], 404);
        }

        try {
            $run = TrustVerificationAutomationService::applyWebhookResults($order, $request->input('results'));

            return response()->json([
                'error' => false,
                'message' => 'Webhook processed',
                'data' => [
                    'run_id' => $run->id,
                    'checks_updated' => $run->checks_updated,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function listReferences(Request $request, TvOrder $order)
    {
        if ((int) $order->customer_id !== (int) $request->user()->id) {
            return response()->json(['error' => true, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'error' => false,
            'message' => 'References fetched successfully',
            'data' => [
                'references' => TrustVerificationReferenceService::formatListForCustomer($order),
                'reference_progress' => TrustVerificationReferenceService::progressForOrder($order),
            ],
        ]);
    }

    public function storeReferences(Request $request, TvOrder $order)
    {
        if ((int) $order->customer_id !== (int) $request->user()->id) {
            return response()->json(['error' => true, 'message' => 'Unauthorized'], 403);
        }

        if (! in_array($order->status, ['submitted', 'in_progress'], true)) {
            return response()->json([
                'error' => true,
                'message' => 'References cannot be added for this order status.',
            ], 422);
        }

        try {
            TrustVerificationReferenceService::createManyForCustomer(
                $order,
                $request->input('references', []),
                $request
            );
            $order->refresh()->load(['package', 'subject', 'checkItems', 'report', 'documents', 'referenceContacts', 'policeVerification']);

            return response()->json([
                'error' => false,
                'message' => 'Reference contacts saved successfully',
                'data' => TrustVerificationService::formatOrder($order, true),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function showPoliceVerification(Request $request, TvOrder $order)
    {
        if ((int) $order->customer_id !== (int) $request->user()->id) {
            return response()->json(['error' => true, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'error' => false,
            'message' => 'Police verification fetched successfully',
            'data' => [
                'police_verification' => TrustVerificationPoliceVerificationService::summaryForCustomer($order),
                'police_verification_enabled' => TrustVerificationPoliceVerificationService::orderRequiresPoliceVerification($order),
            ],
        ]);
    }

    public function storePoliceVerification(Request $request, TvOrder $order)
    {
        if ((int) $order->customer_id !== (int) $request->user()->id) {
            return response()->json(['error' => true, 'message' => 'Unauthorized'], 403);
        }

        try {
            TrustVerificationPoliceVerificationService::upsertFromCustomer(
                $order,
                $request->only([
                    'police_station_name', 'city', 'district',
                    'applicant_mobile', 'reference_number', 'customer_notes',
                ]),
                $request->file('acknowledgement'),
                $request
            );
            $order->refresh()->load(['package', 'subject', 'checkItems', 'report', 'documents', 'referenceContacts', 'policeVerification']);

            return response()->json([
                'error' => false,
                'message' => 'Police verification saved successfully',
                'data' => TrustVerificationService::formatOrder($order, true),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 422);
        } catch (DocumentUploadRejectedException $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function uploadPoliceAcknowledgement(Request $request, TvOrder $order)
    {
        if ((int) $order->customer_id !== (int) $request->user()->id) {
            return response()->json(['error' => true, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'acknowledgement' => 'required|file|max:5120',
        ]);

        try {
            if (! TrustVerificationPoliceVerificationService::orderRequiresPoliceVerification($order)) {
                throw new \InvalidArgumentException('Police verification is not included in this order.');
            }

            $record = TrustVerificationPoliceVerificationService::getOrCreateForOrder($order);
            TrustVerificationPoliceDocumentService::storeAcknowledgement(
                $record,
                $request->file('acknowledgement'),
                $request->user()
            );

            TrustVerificationAuditLogService::logCustomer(
                $request,
                $order,
                TrustVerificationAuditLogService::ACTION_POLICE_ACKNOWLEDGEMENT_UPLOADED,
                'Customer uploaded police verification acknowledgement',
                ['police_verification_id' => $record->id]
            );

            $order->refresh()->load(['package', 'subject', 'checkItems', 'report', 'documents', 'policeVerification']);

            return response()->json([
                'error' => false,
                'message' => 'Acknowledgement uploaded successfully',
                'data' => TrustVerificationService::formatOrder($order, true),
            ]);
        } catch (\InvalidArgumentException|DocumentUploadRejectedException $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function downloadPoliceAcknowledgement(Request $request, TvOrder $order)
    {
        if ((int) $order->customer_id !== (int) $request->user()->id) {
            return response()->json(['error' => true, 'message' => 'Unauthorized'], 403);
        }

        $record = $order->policeVerification;
        if (! $record) {
            return response()->json(['error' => true, 'message' => 'Not found'], 404);
        }

        TrustVerificationAuditLogService::logCustomer(
            $request,
            $order,
            TrustVerificationAuditLogService::ACTION_POLICE_ACKNOWLEDGEMENT_DOWNLOADED,
            'Customer downloaded police acknowledgement',
            ['police_verification_id' => $record->id]
        );

        return TrustVerificationPoliceDocumentService::downloadAcknowledgement(
            $record,
            $request->user()
        );
    }

    public function downloadPoliceCertificate(Request $request, TvOrder $order)
    {
        if ((int) $order->customer_id !== (int) $request->user()->id) {
            return response()->json(['error' => true, 'message' => 'Unauthorized'], 403);
        }

        $record = $order->policeVerification;
        if (! $record || $record->status !== 'completed') {
            return response()->json(['error' => true, 'message' => 'Certificate not available'], 404);
        }

        TrustVerificationAuditLogService::logCustomer(
            $request,
            $order,
            TrustVerificationAuditLogService::ACTION_POLICE_CERTIFICATE_DOWNLOADED,
            'Customer downloaded police certificate',
            ['police_verification_id' => $record->id]
        );

        return TrustVerificationPoliceDocumentService::downloadCertificate(
            $record,
            $request->user()
        );
    }

    public function trustProfile(Request $request)
    {
        $customerId = (int) $request->user()->id;
        $payload = TrustVerificationTrustBadgeService::formatForApi($customerId, false);

        if (! $payload) {
            TrustVerificationTrustBadgeService::refreshScore($customerId, 'profile_view');

            $payload = TrustVerificationTrustBadgeService::formatForApi($customerId, false);
        }

        return response()->json([
            'error' => false,
            'message' => 'Trust profile fetched successfully',
            'data' => $payload ?? [
                'customer_id' => $customerId,
                'trust_score' => 0,
                'manual_adjustment' => 0,
                'public_visible' => true,
                'breakdown' => [],
                'badges' => [],
            ],
        ]);
    }

    public function publicTrust(int $customerId)
    {
        $payload = TrustVerificationTrustBadgeService::formatForApi($customerId, true);

        if (! $payload) {
            return response()->json([
                'error' => false,
                'message' => 'Trust profile not available',
                'data' => null,
            ]);
        }

        return response()->json([
            'error' => false,
            'message' => 'Public trust profile fetched successfully',
            'data' => $payload,
        ]);
    }

    public function myVerificationBadges(Request $request)
    {
        $customerId = (int) $request->user()->id;

        return response()->json([
            'error' => false,
            'message' => 'Verification badges fetched successfully',
            'data' => TrustVerificationIssuedBadgeService::activeBadgesForCustomer($customerId),
        ]);
    }

    public function orderVerificationBadge(Request $request, TvOrder $order)
    {
        if ((int) $order->customer_id !== (int) $request->user()->id) {
            return response()->json(['error' => true, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'error' => false,
            'message' => 'Order verification badge fetched successfully',
            'data' => TrustVerificationIssuedBadgeService::formatForOrder($order),
        ]);
    }

    public function downloadVerificationBadge(Request $request, TvVerificationBadge $verificationBadge)
    {
        if ((int) $verificationBadge->customer_id !== (int) $request->user()->id) {
            return response()->json(['error' => true, 'message' => 'Unauthorized'], 403);
        }

        if (! $verificationBadge->isCurrentlyVerified()) {
            return response()->json(['error' => true, 'message' => 'Badge not available for download'], 404);
        }

        TrustVerificationIssuedBadgeService::logDownloaded($request, $verificationBadge, (int) $request->user()->id);

        return TrustVerificationIssuedBadgeService::certificateDownloadResponse($verificationBadge);
    }

    /**
     * Owner-safe tenant reliability — no fraud, risk, PII, or score breakdown.
     */
    public function publicTenantReliability(int $customerId)
    {
        $payload = TrustVerificationTenantReliabilityService::publicSafeData($customerId, true);

        if (! $payload) {
            return response()->json([
                'error' => false,
                'message' => 'Tenant reliability not available',
                'data' => null,
            ]);
        }

        return response()->json([
            'error' => false,
            'message' => 'Tenant reliability fetched successfully',
            'data' => $payload,
        ]);
    }
}

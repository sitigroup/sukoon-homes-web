<?php



namespace App\Plugins\TrustVerification\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Plugins\TrustVerification\Models\TvAutomationRun;

use App\Plugins\TrustVerification\Services\TrustVerificationAuditLogService;

use App\Plugins\TrustVerification\Services\TrustVerificationAutomationService;

use App\Plugins\TrustVerification\Services\TrustVerificationOrderNumberService;

use App\Plugins\TrustVerification\Services\TrustVerificationPermissionService;

use App\Plugins\TrustVerification\Services\TrustVerificationSettingsService;

use App\Plugins\TrustVerification\Support\ChecksTrustVerificationPermissions;

use Illuminate\Http\Request;

use App\Plugins\TrustVerification\Services\TrustVerificationPaymentService;

use InvalidArgumentException;



class TrustVerificationAutomationAdminController extends Controller

{

    use ChecksTrustVerificationPermissions;



    public function index()

    {

        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {

            return $denied;

        }



        $settings = TrustVerificationSettingsService::all();

        $recentRuns = TvAutomationRun::query()

            ->with('order:id,order_number')

            ->latest('id')

            ->limit(40)

            ->get();



        return view('trust-verification::admin.automation.index', [

            'settings' => $settings,

            'documentTypes' => TrustVerificationSettingsService::DOCUMENT_TYPES,

            'providers' => TrustVerificationAutomationService::providerOptions(),

            'recentRuns' => $recentRuns,

            'automationMeta' => TrustVerificationSettingsService::automationPayload(),

            'orderNumberMeta' => TrustVerificationSettingsService::orderNumberPayload(),

            'previewCitySlug' => 'barmer',

        ]);

    }



    public function update(Request $request)

    {

        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {

            return $denied;

        }



        $data = $request->validate([

            'documents_enabled' => 'nullable|boolean',

            'documents_required' => 'nullable|boolean',

            'required_document_types' => 'nullable|array',

            'required_document_types.*' => 'string|in:'.implode(',', array_keys(TrustVerificationSettingsService::DOCUMENT_TYPES)),

            'automation_enabled' => 'nullable|boolean',

            'automation_provider' => 'required|in:manual,rules,http',

            'automation_on_paid' => 'nullable|boolean',

            'automation_auto_apply' => 'nullable|boolean',

            'http_api_base_url' => 'nullable|string|max:500',

            'http_api_key' => 'nullable|string|max:500',

            'http_api_secret' => 'nullable|string|max:500',

            'webhook_secret' => 'nullable|string|max:120',

            'document_retention_days' => 'nullable|integer|min:1|max:3650',

            'report_retention_days' => 'nullable|integer|min:1|max:3650',

            'delete_cancelled_unpaid_after_days' => 'nullable|integer|min:1|max:365',

            'order_number_prefix' => 'nullable|string|max:8|regex:/^[A-Za-z0-9]+$/',

            'order_number_format' => 'nullable|in:'.implode(',', [

                TrustVerificationOrderNumberService::FORMAT_RANDOM,

                TrustVerificationOrderNumberService::FORMAT_SEQUENTIAL,

                TrustVerificationOrderNumberService::FORMAT_YEAR_SEQUENCE,

                TrustVerificationOrderNumberService::FORMAT_CITY_SEQUENCE,

            ]),

            'order_number_next_sequence' => 'nullable|integer|min:1|max:999999999',

            'order_number_digits' => 'nullable|integer|min:4|max:12',

            'order_number_separator' => 'nullable|string|max:1',

        ]);



        $beforeOrderSeries = TrustVerificationSettingsService::orderNumberPayload();



        try {

            if (array_key_exists('order_number_prefix', $data) && $data['order_number_prefix'] !== null) {

                TrustVerificationOrderNumberService::normalizePrefix($data['order_number_prefix']);

            }

        } catch (InvalidArgumentException $e) {

            return redirect()->route('trust-verification.automation.index')

                ->withInput()

                ->with('error', $e->getMessage());

        }



        TrustVerificationSettingsService::save([

            'documents_enabled' => $request->boolean('documents_enabled'),

            'documents_required' => $request->boolean('documents_required'),

            'required_document_types' => array_values($request->input('required_document_types', [])),

            'automation_enabled' => $request->boolean('automation_enabled'),

            'automation_provider' => $data['automation_provider'],

            'automation_on_paid' => $request->boolean('automation_on_paid'),

            'automation_auto_apply' => $request->boolean('automation_auto_apply'),

            'http_api_base_url' => $data['http_api_base_url'] ?? '',

            'http_api_key' => $data['http_api_key'] ?? '',

            'http_api_secret' => $data['http_api_secret'] ?? '',

            'webhook_secret' => $data['webhook_secret'] ?? '',

            'document_retention_days' => (int) ($data['document_retention_days'] ?? 90),

            'report_retention_days' => (int) ($data['report_retention_days'] ?? 365),

            'delete_cancelled_unpaid_after_days' => (int) ($data['delete_cancelled_unpaid_after_days'] ?? 7),

            'order_number_prefix' => isset($data['order_number_prefix'])

                ? strtoupper($data['order_number_prefix'])

                : ($beforeOrderSeries['order_number_prefix'] ?? 'TV'),

            'order_number_format' => $data['order_number_format'] ?? ($beforeOrderSeries['order_number_format'] ?? TrustVerificationOrderNumberService::FORMAT_RANDOM),

            'order_number_next_sequence' => (int) ($data['order_number_next_sequence'] ?? ($beforeOrderSeries['order_number_next_sequence'] ?? 1)),

            'order_number_digits' => (int) ($data['order_number_digits'] ?? ($beforeOrderSeries['order_number_digits'] ?? 6)),

            'order_number_separator' => $data['order_number_separator'] ?? ($beforeOrderSeries['order_number_separator'] ?? '-'),

        ]);



        $afterOrderSeries = TrustVerificationSettingsService::orderNumberPayload();



        TrustVerificationAuditLogService::log([

            'admin_id' => auth()->id(),

            'action' => TrustVerificationAuditLogService::ACTION_ORDER_SERIES_SETTINGS_UPDATED,

            'description' => 'Trust Verification order number series settings updated',

            'metadata' => [

                'before' => $beforeOrderSeries,

                'after' => $afterOrderSeries,

            ],

        ], $request);



        return redirect()->route('trust-verification.automation.index')->with('success', 'Automation settings saved');

    }



    public function reconcilePayments(Request $request)

    {

        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {

            return $denied;

        }



        $dryRun = $request->has('dry_run');



        try {

            $result = TrustVerificationPaymentService::reconcilePendingPayments($dryRun);

            $message = str_replace(["\r\n", "\n"], ' · ', $result['message']);

        } catch (\Throwable $e) {

            report($e);



            return redirect()->route('trust-verification.automation.index')

                ->with('error', 'Reconciliation failed: '.$e->getMessage());

        }



        return redirect()->route('trust-verification.automation.index')

            ->with('success', $message);

    }

}



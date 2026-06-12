<?php

namespace App\Plugins\TrustVerification\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\TrustVerification\Models\TvPackage;
use App\Plugins\TrustVerification\Models\TvSampleReport;
use App\Plugins\TrustVerification\Services\TrustVerificationAuditLogService;
use App\Plugins\TrustVerification\Services\TrustVerificationPermissionService;
use App\Plugins\TrustVerification\Services\TrustVerificationSampleReportService;
use App\Plugins\TrustVerification\Services\TrustVerificationService;
use App\Plugins\TrustVerification\Support\ChecksTrustVerificationPermissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrustVerificationSampleReportAdminController extends Controller
{
    use ChecksTrustVerificationPermissions;

    public function index(Request $request)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        $filters = [
            'report_type' => (string) $request->query('report_type', ''),
            'is_active' => (string) $request->query('is_active', ''),
        ];

        $query = TvSampleReport::query()->with('package')->orderBy('report_type')->orderBy('sort_order')->orderBy('id');

        if (in_array($filters['report_type'], TrustVerificationSampleReportService::reportTypes(), true)) {
            $query->where('report_type', $filters['report_type']);
        }

        if ($filters['is_active'] === '1') {
            $query->where('is_active', true);
        } elseif ($filters['is_active'] === '0') {
            $query->where('is_active', false);
        }

        return view('trust-verification::admin.sample-reports.index', [
            'samples' => $query->get(),
            'filters' => $filters,
            'cityCatalog' => TrustVerificationService::cityCatalog(),
            'packages' => TvPackage::query()->orderBy('city_slug')->orderBy('type')->orderBy('name')->get(),
            'defaultChecks' => TrustVerificationSampleReportService::defaultChecks(),
        ]);
    }

    public function create()
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        return view('trust-verification::admin.sample-reports.form', [
            'sample' => new TvSampleReport([
                'report_type' => TvSampleReport::TYPE_TENANT,
                'is_active' => true,
                'sort_order' => 0,
            ]),
            'isEdit' => false,
            'cityCatalog' => TrustVerificationService::cityCatalog(),
            'packages' => TvPackage::query()->orderBy('city_slug')->orderBy('type')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        $data = $this->validated($request, true);
        $sample = TvSampleReport::query()->create($data);

        if ($request->hasFile('sample_pdf')) {
            try {
                TrustVerificationSampleReportService::storePdf($sample, $request->file('sample_pdf'));
                TrustVerificationAuditLogService::log([
                    'admin_id' => auth()->id(),
                    'action' => TrustVerificationAuditLogService::ACTION_ADMIN_UPLOADED_SAMPLE_REPORT,
                    'description' => 'Uploaded sample report PDF',
                    'metadata' => ['sample_report_id' => $sample->id, 'report_type' => $sample->report_type],
                ], $request);
            } catch (\Throwable $e) {
                $sample->delete();

                return back()->withInput()->with('error', $e->getMessage());
            }
        }

        return redirect()
            ->route('trust-verification.sample-reports.index')
            ->with('success', 'Sample report saved.');
    }

    public function edit(TvSampleReport $sampleReport)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        return view('trust-verification::admin.sample-reports.form', [
            'sample' => $sampleReport,
            'isEdit' => true,
            'cityCatalog' => TrustVerificationService::cityCatalog(),
            'packages' => TvPackage::query()->orderBy('city_slug')->orderBy('type')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, TvSampleReport $sampleReport)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        $data = $this->validated($request, false);
        $sampleReport->update($data);

        if ($request->hasFile('sample_pdf')) {
            try {
                TrustVerificationSampleReportService::storePdf($sampleReport, $request->file('sample_pdf'));
                TrustVerificationAuditLogService::log([
                    'admin_id' => auth()->id(),
                    'action' => TrustVerificationAuditLogService::ACTION_ADMIN_UPLOADED_SAMPLE_REPORT,
                    'description' => 'Replaced sample report PDF',
                    'metadata' => ['sample_report_id' => $sampleReport->id, 'report_type' => $sampleReport->report_type],
                ], $request);
            } catch (\Throwable $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }
        }

        return redirect()
            ->route('trust-verification.sample-reports.index')
            ->with('success', 'Sample report updated.');
    }

    public function destroy(Request $request, TvSampleReport $sampleReport)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        TrustVerificationSampleReportService::deleteStoredFile($sampleReport->file_path);
        $sampleReport->delete();

        return redirect()
            ->route('trust-verification.sample-reports.index')
            ->with('success', 'Sample report removed.');
    }

    public function download(TvSampleReport $sampleReport)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        return TrustVerificationSampleReportService::downloadResponse($sampleReport, true);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, bool $requirePdfOnCreate): array
    {
        $rules = [
            'report_type' => ['required', Rule::in(TrustVerificationSampleReportService::reportTypes())],
            'title' => 'required|string|max:160',
            'description' => 'nullable|string|max:2000',
            'city_slug' => 'nullable|string|max:80',
            'package_id' => 'nullable|integer|exists:tv_packages,id',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'sample_pdf' => ($requirePdfOnCreate ? 'required' : 'nullable').'|file|mimes:pdf|max:10240',
        ];

        $validated = $request->validate($rules);

        return [
            'report_type' => $validated['report_type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'city_slug' => $validated['city_slug'] ?: null,
            'package_id' => $validated['package_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ];
    }
}

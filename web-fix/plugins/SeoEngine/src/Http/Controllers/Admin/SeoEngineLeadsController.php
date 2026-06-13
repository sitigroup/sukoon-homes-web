<?php

namespace App\Plugins\SeoEngine\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\SeoEngine\Models\SeoEngineLead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeoEngineLeadsController extends Controller
{
    private function denyUnlessLeads(): void
    {
        if (! function_exists('has_permissions') || ! has_permissions('leads', 'seo_engine')) {
            abort(403);
        }
    }

    public function index(Request $request): View
    {
        $this->denyUnlessLeads();

        $status = trim((string) $request->query('status', ''));
        $areaId = $request->query('area_id');

        $leads = SeoEngineLead::query()
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($areaId !== null && $areaId !== '', fn ($q) => $q->where('area_id', (int) $areaId))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $areaIds = SeoEngineLead::query()
            ->whereNotNull('area_id')
            ->distinct()
            ->orderBy('area_id')
            ->pluck('area_id');

        return view('seo-engine::admin.seo-engine.leads.index', [
            'leads' => $leads,
            'status' => $status,
            'areaId' => $areaId,
            'areaIds' => $areaIds,
        ]);
    }

    public function update(Request $request, SeoEngineLead $lead): RedirectResponse
    {
        $this->denyUnlessLeads();

        $validated = $request->validate([
            'status' => ['required', 'in:new,contacted,closed'],
        ]);

        $lead->update(['status' => $validated['status']]);

        return back()->with('success', __('seo-engine::seo_engine.lead_updated'));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->denyUnlessLeads();

        $status = trim((string) $request->query('status', ''));
        $filename = 'seo-engine-leads-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($status) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'name', 'phone', 'requirement', 'source_path', 'area_id', 'form_type', 'status', 'created_at']);

            if (! Schema::hasTable('seo_engine_leads')) {
                fclose($out);

                return;
            }

            SeoEngineLead::query()
                ->when($status !== '', fn ($q) => $q->where('status', $status))
                ->orderByDesc('created_at')
                ->chunk(200, function ($rows) use ($out) {
                    foreach ($rows as $lead) {
                        fputcsv($out, [
                            $lead->id,
                            $lead->name,
                            $lead->phone,
                            $lead->requirement,
                            $lead->source_path,
                            $lead->area_id,
                            $lead->form_type,
                            $lead->status,
                            optional($lead->created_at)->toDateTimeString(),
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}

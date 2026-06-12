<?php

namespace App\Plugins\Whatsapp\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\Whatsapp\Services\WhatsappBatchReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsappBatchReminderController extends Controller
{
    public function __construct(private readonly WhatsappBatchReminderService $service) {}

    private function denyUnlessEvents(): void
    {
        if (! function_exists('has_permissions')) {
            abort(403);
        }

        if (has_permissions('events', 'whatsapp') || has_permissions('templates', 'whatsapp')) {
            return;
        }

        abort(403);
    }

    public function index(Request $request): View
    {
        $this->denyUnlessEvents();

        $availability = $this->service->availability();
        $recentBatches = $this->service->recentBatches();
        $batchDetail = null;

        $batchId = (string) $request->query('batch', '');
        if ($batchId !== '') {
            $batchDetail = $this->service->batchRows($batchId);
        }

        return view('whatsapp::admin.whatsapp.batch-reminders', compact(
            'availability',
            'recentBatches',
            'batchDetail',
            'batchId'
        ));
    }

    public function previewRent(Request $request): RedirectResponse
    {
        $this->denyUnlessEvents();

        $validated = $request->validate([
            'due_date' => 'required|date',
        ]);

        $preview = $this->service->previewRent($validated['due_date']);

        return back()
            ->with('batch_tab', 'rent')
            ->with('rent_preview', $preview);
    }

    public function runRent(Request $request): RedirectResponse
    {
        $this->denyUnlessEvents();

        $validated = $request->validate([
            'due_date' => 'required|date',
            'confirm' => 'accepted',
        ]);

        $report = $this->service->runRent($validated['due_date'], auth()->id());

        return redirect()
            ->route('whatsapp.batch-reminders.index', ['batch' => $report['batch_id']])
            ->with('batch_tab', 'rent')
            ->with('success', __('whatsapp::whatsapp.batch_rent_done', [
                'sent' => $report['sent'],
                'skipped' => $report['skipped'],
                'failed' => $report['failed'],
            ]));
    }

    public function previewRenewal(): RedirectResponse
    {
        $this->denyUnlessEvents();

        $preview = $this->service->previewRenewal();

        return back()
            ->with('batch_tab', 'renewal')
            ->with('renewal_preview', $preview);
    }

    public function runRenewal(Request $request): RedirectResponse
    {
        $this->denyUnlessEvents();

        $request->validate([
            'confirm' => 'accepted',
        ]);

        $report = $this->service->runRenewal(auth()->id());

        return redirect()
            ->route('whatsapp.batch-reminders.index', ['batch' => $report['batch_id']])
            ->with('batch_tab', 'renewal')
            ->with('success', __('whatsapp::whatsapp.batch_renewal_done', [
                'sent' => $report['reminders_sent'],
                'skipped' => $report['skipped'],
            ]))
            ->with('renewal_report', $report);
    }
}

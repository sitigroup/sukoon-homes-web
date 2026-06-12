<?php

namespace App\Plugins\SeoEngine\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\SeoEngine\Models\SeoEngineRedirect;
use App\Plugins\SeoEngine\Services\SeoEngineRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeoEngineRedirectsController extends Controller
{
    private function denyUnlessRedirects(): void
    {
        if (! function_exists('has_permissions') || ! has_permissions('redirects', 'seo_engine')) {
            abort(403);
        }
    }

    public function index(Request $request): View
    {
        $this->denyUnlessRedirects();

        $q = trim((string) $request->query('q', ''));
        $redirects = SeoEngineRedirect::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('from_path', 'like', '%' . $q . '%')
                    ->orWhere('to_path', 'like', '%' . $q . '%');
            })
            ->orderByDesc('hits')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('seo-engine::admin.seo-engine.redirects', compact('redirects', 'q'));
    }

    public function store(Request $request, SeoEngineRedirectService $service): RedirectResponse
    {
        $this->denyUnlessRedirects();

        $validated = $request->validate([
            'from_path' => ['required', 'string', 'max:512'],
            'to_path' => ['required', 'string', 'max:512'],
            'status_code' => ['required', 'in:301,302'],
        ]);

        $redirect = $service->upsertRedirect(
            $validated['from_path'],
            $validated['to_path'],
            (int) $validated['status_code']
        );

        if (! $redirect) {
            return back()->with('error', __('seo-engine::seo_engine.redirect_loop_prevented'));
        }

        return back()->with('success', __('seo-engine::seo_engine.redirect_saved'));
    }

    public function update(Request $request, SeoEngineRedirect $redirect, SeoEngineRedirectService $service): RedirectResponse
    {
        $this->denyUnlessRedirects();

        $validated = $request->validate([
            'from_path' => ['required', 'string', 'max:512'],
            'to_path' => ['required', 'string', 'max:512'],
            'status_code' => ['required', 'in:301,302'],
        ]);

        if ($redirect->from_path !== $service->normalizePath($validated['from_path'])) {
            return back()->with('error', __('seo-engine::seo_engine.redirect_from_immutable'));
        }

        $updated = $service->upsertRedirect(
            $validated['from_path'],
            $validated['to_path'],
            (int) $validated['status_code']
        );

        if (! $updated) {
            return back()->with('error', __('seo-engine::seo_engine.redirect_loop_prevented'));
        }

        return back()->with('success', __('seo-engine::seo_engine.redirect_saved'));
    }

    public function destroy(SeoEngineRedirect $redirect, SeoEngineRedirectService $service): RedirectResponse
    {
        $this->denyUnlessRedirects();
        $service->clearPathCache($redirect->from_path);
        $redirect->delete();

        return back()->with('success', __('seo-engine::seo_engine.redirect_deleted'));
    }

    public function import(Request $request, SeoEngineRedirectService $service): RedirectResponse
    {
        $this->denyUnlessRedirects();

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
        if (! $handle) {
            return back()->with('error', __('seo-engine::seo_engine.redirect_csv_invalid'));
        }

        $imported = 0;
        $skipped = 0;
        $header = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                $skipped++;
                continue;
            }
            $status = isset($row[2]) && (int) $row[2] === 302 ? 302 : 301;
            $result = $service->upsertRedirect($row[0], $row[1], $status);
            $result ? $imported++ : $skipped++;
        }
        fclose($handle);

        return back()->with('success', __('seo-engine::seo_engine.redirect_csv_done', [
            'imported' => $imported,
            'skipped' => $skipped,
        ]));
    }
}

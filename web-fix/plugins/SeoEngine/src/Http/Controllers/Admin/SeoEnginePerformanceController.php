<?php

namespace App\Plugins\SeoEngine\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\SeoEngine\Services\SeoEngineGscService;
use App\Plugins\SeoEngine\Services\SeoEngineLeadService;
use App\Plugins\SeoEngine\Services\SeoEngineSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SeoEnginePerformanceController extends Controller
{
    private function denyUnlessDashboard(): void
    {
        if (! function_exists('has_permissions') || ! has_permissions('dashboard', 'seo_engine')) {
            abort(403);
        }
    }

    public function index(
        SeoEngineGscService $gsc,
        SeoEngineLeadService $leads,
        SeoEngineSettingsService $settings
    ): View {
        $this->denyUnlessDashboard();

        $metrics = $gsc->dashboardMetrics();
        $leadCounts = $leads->statusCounts();
        $ga4Enabled = (bool) $settings->get('ga4_enabled', false);

        return view('seo-engine::admin.seo-engine.performance', [
            'metrics' => $metrics,
            'leadCounts' => $leadCounts,
            'gscConfigured' => $gsc->isConfigured(),
            'gscConnected' => $gsc->isConnected(),
            'ga4Enabled' => $ga4Enabled,
            'ga4Id' => (string) $settings->get('ga4_measurement_id', ''),
        ]);
    }

    public function connectGsc(Request $request, SeoEngineGscService $gsc): RedirectResponse
    {
        $this->denyUnlessDashboard();

        $state = Str::random(40);
        session(['seo_engine_gsc_oauth_state' => $state]);

        $redirectUri = route('seo-engine.performance.gsc-callback');

        return redirect()->away($gsc->authUrl($redirectUri, $state));
    }

    public function gscCallback(Request $request, SeoEngineGscService $gsc): RedirectResponse
    {
        $this->denyUnlessDashboard();

        $expected = (string) session('seo_engine_gsc_oauth_state', '');
        session()->forget('seo_engine_gsc_oauth_state');

        if ($expected === '' || ! hash_equals($expected, (string) $request->query('state', ''))) {
            return redirect()->route('seo-engine.performance.index')->with('error', __('seo-engine::seo_engine.gsc_oauth_failed'));
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect()->route('seo-engine.performance.index')->with('error', __('seo-engine::seo_engine.gsc_oauth_failed'));
        }

        $ok = $gsc->exchangeCode($code, route('seo-engine.performance.gsc-callback'));
        if (! $ok) {
            return redirect()->route('seo-engine.performance.index')->with('error', __('seo-engine::seo_engine.gsc_oauth_failed'));
        }

        $gsc->syncDaily();

        return redirect()->route('seo-engine.performance.index')->with('success', __('seo-engine::seo_engine.gsc_connected'));
    }

    public function syncGsc(SeoEngineGscService $gsc): RedirectResponse
    {
        $this->denyUnlessDashboard();

        if (! $gsc->syncDaily()) {
            return back()->with('error', __('seo-engine::seo_engine.gsc_sync_failed'));
        }

        return back()->with('success', __('seo-engine::seo_engine.gsc_synced'));
    }

    public function disconnectGsc(SeoEngineGscService $gsc): RedirectResponse
    {
        $this->denyUnlessDashboard();
        $gsc->disconnect();

        return back()->with('success', __('seo-engine::seo_engine.gsc_disconnected'));
    }
}

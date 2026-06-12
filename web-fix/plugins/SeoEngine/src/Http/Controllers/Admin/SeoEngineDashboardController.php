<?php

namespace App\Plugins\SeoEngine\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\SeoEngine\Models\SeoEnginePage;
use App\Plugins\SeoEngine\Models\SeoEngineRedirect;
use App\Plugins\SeoEngine\Services\SeoEngineSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SeoEngineDashboardController extends Controller
{
    private function denyUnlessDashboard(): void
    {
        if (! function_exists('has_permissions') || ! has_permissions('dashboard', 'seo_engine')) {
            abort(403);
        }
    }

    public function index(SeoEngineSettingsService $settings): View
    {
        $this->denyUnlessDashboard();

        $stats = [
            'indexable_count' => 0,
            'missing_content_count' => 0,
            'redirect_hits' => 0,
        ];

        if (Schema::hasTable('seo_engine_pages')) {
            $stats['indexable_count'] = SeoEnginePage::query()->where('is_indexable', true)->count();
            $stats['missing_content_count'] = SeoEnginePage::query()
                ->where(function ($q) {
                    $q->whereNull('intro_html')->orWhere('intro_html', '');
                })
                ->count();
        }

        if (Schema::hasTable('seo_engine_redirects')) {
            $stats['redirect_hits'] = (int) SeoEngineRedirect::query()->sum('hits');
        }

        $cron = [
            'generate_pages' => $settings->get('cron_last_generate_pages_at'),
            'build_sitemaps' => $settings->get('cron_last_build_sitemaps_at'),
            'generate_content' => $settings->get('cron_last_generate_content_at'),
        ];

        return view('seo-engine::admin.seo-engine.dashboard', compact('stats', 'cron'));
    }

    public function regeneratePages(): RedirectResponse
    {
        $this->denyUnlessDashboard();
        \Illuminate\Support\Facades\Artisan::call('seo-engine:generate-pages');

        return back()->with('success', __('seo-engine::seo_engine.regenerate_pages_done'));
    }

    public function regenerateSitemaps(): RedirectResponse
    {
        $this->denyUnlessDashboard();
        \Illuminate\Support\Facades\Artisan::call('seo-engine:build-sitemaps');

        return back()->with('success', __('seo-engine::seo_engine.regenerate_sitemaps_done'));
    }
}

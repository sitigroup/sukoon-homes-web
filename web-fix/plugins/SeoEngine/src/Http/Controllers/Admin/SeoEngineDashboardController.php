<?php

namespace App\Plugins\SeoEngine\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\SeoEngine\Models\SeoEngine404Log;
use App\Plugins\SeoEngine\Models\SeoEnginePage;
use App\Plugins\SeoEngine\Models\SeoEngineRedirect;
use App\Plugins\SeoEngine\Services\SeoEnginePageGeneratorService;
use App\Plugins\SeoEngine\Services\SeoEngineQaSitemapService;
use App\Plugins\SeoEngine\Services\SeoEngineRentSitemapService;
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
            'qa_draft_count' => 0,
            'qa_published_count' => 0,
            '404_hits_week' => 0,
        ];

        if (Schema::hasTable('seo_engine_pages')) {
            $stats['indexable_count'] = SeoEnginePage::query()->where('is_indexable', true)->count();
            $stats['missing_content_count'] = SeoEnginePage::query()
                ->where('listing_count', '>=', max(1, (int) $settings->get('ai_content_min_listings', 1)))
                ->where(function ($q) {
                    $q->whereNull('intro_html')->orWhere('intro_html', '');
                })
                ->count();
        }

        if (Schema::hasTable('seo_engine_qa_pages')) {
            $stats['qa_draft_count'] = \App\Plugins\SeoEngine\Models\SeoEngineQaPage::query()->where('status', 'draft')->count();
            $stats['qa_published_count'] = \App\Plugins\SeoEngine\Models\SeoEngineQaPage::query()->where('status', 'published')->count();
        }

        if (Schema::hasTable('seo_engine_404_log')) {
            $stats['404_hits_week'] = (int) SeoEngine404Log::query()
                ->where('last_seen_at', '>=', now()->subDays(7))
                ->sum('hit_count');
        }

        if (Schema::hasTable('seo_engine_redirects')) {
            $stats['redirect_hits'] = (int) SeoEngineRedirect::query()->sum('hits');
        }

        $cron = [
            'generate_pages' => $settings->get('cron_last_generate_pages_at'),
            'build_sitemaps' => $settings->get('cron_last_build_sitemaps_at'),
            'generate_content' => $settings->get('cron_last_generate_content_at'),
            '404_digest' => $settings->get('cron_last_404_digest_at'),
        ];

        $indexnowLog = array_slice((array) $settings->get('indexnow_log', []), 0, 10);
        $digest404 = $settings->get('404_digest_latest', []);

        return view('seo-engine::admin.seo-engine.dashboard', compact('stats', 'cron', 'indexnowLog', 'digest404'));
    }

    public function regeneratePages(
        SeoEnginePageGeneratorService $generator,
        SeoEngineRentSitemapService $sitemap
    ): RedirectResponse {
        $this->denyUnlessDashboard();

        try {
            $generator->generate();
            $sitemap->export();
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['regenerate' => $e->getMessage()]);
        }

        return back()->with('success', __('seo-engine::seo_engine.regenerate_pages_done'));
    }

    public function regenerateSitemaps(
        SeoEngineRentSitemapService $rentSitemap,
        SeoEngineQaSitemapService $qaSitemap
    ): RedirectResponse {
        $this->denyUnlessDashboard();

        try {
            $rentSitemap->export();
            $qaSitemap->export();
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['regenerate' => $e->getMessage()]);
        }

        return back()->with('success', __('seo-engine::seo_engine.regenerate_sitemaps_done'));
    }
}

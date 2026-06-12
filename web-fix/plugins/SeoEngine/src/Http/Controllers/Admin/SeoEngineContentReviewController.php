<?php

namespace App\Plugins\SeoEngine\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\SeoEngine\Models\SeoEnginePage;
use App\Plugins\SeoEngine\Services\SeoEngineContentService;
use App\Plugins\SeoEngine\Services\SeoEnginePageDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeoEngineContentReviewController extends Controller
{
    private function denyUnlessPages(): void
    {
        if (! function_exists('has_permissions') || ! has_permissions('pages', 'seo_engine')) {
            abort(403);
        }
    }

    public function index(Request $request): View
    {
        $this->denyUnlessPages();

        $status = $request->query('status', 'pending');
        $query = SeoEnginePage::query()
            ->whereNotNull('intro_html')
            ->where('intro_html', '!=', '')
            ->orderByDesc('content_generated_at');

        if ($status === 'pending') {
            $query->where('content_review_status', 'pending');
        } elseif ($status === 'approved') {
            $query->where('content_review_status', 'approved');
        }

        $pages = $query->paginate(20)->withQueryString();

        return view('seo-engine::admin.seo-engine.content-review', compact('pages', 'status'));
    }

    public function approve(SeoEnginePage $page): RedirectResponse
    {
        $this->denyUnlessPages();
        $page->update(['content_review_status' => 'approved']);

        return back()->with('success', __('seo-engine::seo_engine.content_approved'));
    }

    public function lock(SeoEnginePage $page): RedirectResponse
    {
        $this->denyUnlessPages();
        $page->update(['lock_content' => true, 'content_review_status' => 'approved']);

        return back()->with('success', __('seo-engine::seo_engine.content_locked'));
    }

    public function regenerate(SeoEnginePage $page, SeoEngineContentService $content): RedirectResponse
    {
        $this->denyUnlessPages();
        if ($page->lock_content) {
            return back()->with('error', __('seo-engine::seo_engine.content_locked_skip'));
        }

        $ok = $content->generateForPage($page);
        app(SeoEnginePageDataService::class)->clearPageCache($page->path);

        return back()->with($ok ? 'success' : 'error', $ok
            ? __('seo-engine::seo_engine.content_regenerated')
            : __('seo-engine::seo_engine.content_regenerate_failed'));
    }
}

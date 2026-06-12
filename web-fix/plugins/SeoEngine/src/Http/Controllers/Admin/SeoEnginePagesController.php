<?php

namespace App\Plugins\SeoEngine\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\SeoEngine\Models\SeoEnginePage;
use App\Plugins\SeoEngine\Services\SeoEnginePageDataService;
use App\Plugins\SeoEngine\Services\SeoEnginePageGeneratorService;
use App\Plugins\SeoEngine\Services\SeoEngineTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeoEnginePagesController extends Controller
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

        $q = trim((string) $request->query('q', ''));
        $type = trim((string) $request->query('type', ''));
        $indexable = $request->query('indexable');
        $content = $request->query('content');

        $pages = SeoEnginePage::query()
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('path', 'like', '%' . $q . '%')
                    ->orWhere('title', 'like', '%' . $q . '%');
            }))
            ->when($type !== '', fn ($query) => $query->where('page_type', $type))
            ->when($indexable === '1', fn ($query) => $query->where('is_indexable', true))
            ->when($indexable === '0', fn ($query) => $query->where('is_indexable', false))
            ->when($content === 'missing', fn ($query) => $query->where(function ($w) {
                $w->whereNull('intro_html')->orWhere('intro_html', '');
            }))
            ->when($content === 'has', fn ($query) => $query->where('intro_html', '!=', '')->whereNotNull('intro_html'))
            ->orderByDesc('listing_count')
            ->orderBy('path')
            ->paginate(30)
            ->withQueryString();

        $types = SeoEnginePage::query()->distinct()->orderBy('page_type')->pluck('page_type');

        return view('seo-engine::admin.seo-engine.pages.index', compact('pages', 'q', 'type', 'indexable', 'content', 'types'));
    }

    public function edit(SeoEnginePage $page): View
    {
        $this->denyUnlessPages();

        return view('seo-engine::admin.seo-engine.pages.edit', compact('page'));
    }

    public function update(Request $request, SeoEnginePage $page, SeoEnginePageDataService $data): RedirectResponse
    {
        $this->denyUnlessPages();

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:512'],
            'h1' => ['nullable', 'string', 'max:512'],
            'meta_description' => ['nullable', 'string', 'max:2000'],
            'intro_html' => ['nullable', 'string', 'max:50000'],
            'faq_json' => ['nullable', 'string'],
            'is_indexable' => ['nullable', 'boolean'],
            'lock_content' => ['nullable', 'boolean'],
        ]);

        $faq = null;
        if (! empty($validated['faq_json'])) {
            $decoded = json_decode($validated['faq_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(['faq_json' => 'Invalid JSON'])->withInput();
            }
            $faq = $decoded;
        }

        $page->update([
            'title' => $validated['title'] ?? $page->title,
            'h1' => $validated['h1'] ?? $page->h1,
            'meta_description' => $validated['meta_description'] ?? $page->meta_description,
            'intro_html' => $validated['intro_html'] ?? $page->intro_html,
            'faq_json' => $faq ?? $page->faq_json,
            'is_indexable' => $request->boolean('is_indexable'),
            'lock_content' => $request->boolean('lock_content'),
        ]);

        $data->clearPageCache($page->path);

        return redirect()->route('seo-engine.pages.edit', $page)->with('success', __('seo-engine::seo_engine.page_saved'));
    }

    public function bulk(Request $request, SeoEngineTemplateService $templates, SeoEnginePageDataService $data): RedirectResponse
    {
        $this->denyUnlessPages();

        $validated = $request->validate([
            'action' => ['required', 'in:regenerate_meta,request_content'],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $pages = SeoEnginePage::query()->whereIn('id', $validated['ids'])->get();
        $count = 0;

        foreach ($pages as $page) {
            if ($validated['action'] === 'regenerate_meta' && ! $page->lock_content) {
                $metrics = app(SeoEnginePageGeneratorService::class)->countListings($page->params ?? []);
                $labels = is_array($page->params) ? $page->params : [];
                $meta = $templates->render($page->page_type, array_merge($labels, [
                    'count' => $metrics['count'],
                    'avg_rent' => $metrics['avg_rent'] ?? '',
                    'min_rent' => $labels['min_rent'] ?? ($metrics['min_rent'] ?? ''),
                    'max_rent' => $labels['max_rent'] ?? ($metrics['max_rent'] ?? ''),
                    'band_label' => $labels['band_label'] ?? '',
                ]));
                $page->update([
                    'title' => $meta['title'],
                    'h1' => $meta['h1'],
                    'meta_description' => $meta['meta_description'],
                    'listing_count' => $metrics['count'],
                ]);
                $data->clearPageCache($page->path);
                $count++;
            }
            if ($validated['action'] === 'request_content' && ! $page->lock_content) {
                $page->update(['content_generated_at' => null]);
                $data->clearPageCache($page->path);
                $count++;
            }
        }

        return back()->with('success', __('seo-engine::seo_engine.bulk_done', ['count' => $count]));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->denyUnlessPages();

        $filename = 'seo-engine-pages-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['path', 'page_type', 'listing_count', 'quality_score', 'is_indexable', 'title', 'lock_content']);
            SeoEnginePage::query()->orderBy('path')->chunk(200, function ($rows) use ($out) {
                foreach ($rows as $page) {
                    fputcsv($out, [
                        $page->path,
                        $page->page_type,
                        $page->listing_count,
                        $page->quality_score,
                        $page->is_indexable ? '1' : '0',
                        $page->title,
                        $page->lock_content ? '1' : '0',
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}

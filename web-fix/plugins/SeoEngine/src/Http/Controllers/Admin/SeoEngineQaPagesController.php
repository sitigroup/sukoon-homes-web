<?php

namespace App\Plugins\SeoEngine\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\SeoEngine\Models\SeoEngineQaPage;
use App\Plugins\SeoEngine\Services\SeoEngineQaSitemapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SeoEngineQaPagesController extends Controller
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

        $status = trim((string) $request->query('status', ''));
        $category = trim((string) $request->query('category', ''));
        $q = trim((string) $request->query('q', ''));

        $pages = SeoEngineQaPage::query()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->when($q !== '', fn ($query) => $query->where('question', 'like', '%' . $q . '%'))
            ->orderByDesc('updated_at')
            ->paginate(25)
            ->withQueryString();

        $categories = SeoEngineQaPage::query()->distinct()->orderBy('category')->pluck('category');

        return view('seo-engine::admin.seo-engine.qa.index', compact('pages', 'status', 'category', 'q', 'categories'));
    }

    public function create(): View
    {
        $this->denyUnlessPages();

        return view('seo-engine::admin.seo-engine.qa.form', ['page' => new SeoEngineQaPage(['status' => 'draft'])]);
    }

    public function store(Request $request, SeoEngineQaSitemapService $sitemap): RedirectResponse
    {
        $this->denyUnlessPages();
        $validated = $this->validatePage($request);
        $validated['slug'] = $this->uniqueSlug($validated['slug'], $validated['category']);

        SeoEngineQaPage::query()->create($validated);
        $this->bustQaCache($validated['category'], $validated['slug']);
        $sitemap->export();

        return redirect()->route('seo-engine.qa.index')->with('success', __('seo-engine::seo_engine.qa_saved'));
    }

    public function edit(SeoEngineQaPage $qaPage): View
    {
        $this->denyUnlessPages();

        return view('seo-engine::admin.seo-engine.qa.form', ['page' => $qaPage]);
    }

    public function show(SeoEngineQaPage $qaPage): RedirectResponse
    {
        $this->denyUnlessPages();

        return redirect()->route('seo-engine.qa.edit', $qaPage);
    }

    public function update(Request $request, SeoEngineQaPage $qaPage, SeoEngineQaSitemapService $sitemap): RedirectResponse
    {
        $this->denyUnlessPages();

        $validator = Validator::make($request->all(), [
            'question' => ['required', 'string', 'max:512'],
            'direct_answer' => ['nullable', 'string', 'max:500'],
            'body_html' => ['nullable', 'string', 'max:50000'],
            'category' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9-]+$/'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/'],
            'status' => ['required', 'in:draft,published'],
            'related_rent_links' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('seo-engine.qa.edit', $qaPage)
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $this->normalizeValidated($validator->validated());
        $validated['slug'] = $this->uniqueSlug($validated['slug'], $validated['category'], $qaPage->id);

        $oldCategory = $qaPage->category;
        $oldSlug = $qaPage->slug;

        $qaPage->update($validated);
        $this->bustQaCache($oldCategory, $oldSlug);
        $this->bustQaCache($validated['category'], $validated['slug']);
        $sitemap->export();

        $message = $validated['status'] === 'published'
            ? __('seo-engine::seo_engine.qa_published')
            : __('seo-engine::seo_engine.qa_saved');

        return redirect()
            ->route('seo-engine.qa.edit', $qaPage)
            ->with('success', $message);
    }

    public function destroy(SeoEngineQaPage $qaPage, SeoEngineQaSitemapService $sitemap): RedirectResponse
    {
        $this->denyUnlessPages();
        $category = $qaPage->category;
        $slug = $qaPage->slug;
        $qaPage->delete();
        $this->bustQaCache($category, $slug);
        $sitemap->export();

        return back()->with('success', __('seo-engine::seo_engine.qa_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePage(Request $request): array
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:512'],
            'direct_answer' => ['nullable', 'string', 'max:500'],
            'body_html' => ['nullable', 'string', 'max:50000'],
            'category' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9-]+$/'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/'],
            'status' => ['required', 'in:draft,published'],
            'related_rent_links' => ['nullable', 'string'],
        ]);

        return $this->normalizeValidated($validated);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeValidated(array $validated): array
    {
        $slug = trim((string) ($validated['slug'] ?? ''));
        if ($slug === '') {
            $slug = Str::slug(Str::limit($validated['question'], 80, ''));
        }

        $links = [];
        foreach (preg_split('/\r\n|\r|\n/', (string) ($validated['related_rent_links'] ?? '')) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '' && str_starts_with($line, '/rent/')) {
                $links[] = $line;
            }
        }

        return [
            'question' => $validated['question'],
            'direct_answer' => $validated['direct_answer'] ?? null,
            'body_html' => $validated['body_html'] ?? null,
            'category' => $validated['category'],
            'slug' => $slug,
            'status' => $validated['status'],
            'related_rent_links' => $links ?: null,
        ];
    }

    private function uniqueSlug(string $slug, string $category, ?int $ignoreId = null): string
    {
        $base = $slug;
        $i = 0;
        while (true) {
            $candidate = $i === 0 ? $base : $base . '-' . $i;
            $exists = SeoEngineQaPage::query()
                ->where('category', $category)
                ->where('slug', $candidate)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists();
            if (! $exists) {
                return $candidate;
            }
            $i++;
        }
    }

    private function bustQaCache(string $category, string $slug): void
    {
        Cache::forget('seo_engine:api:qa:' . $category . ':' . $slug);
        Cache::forget('seo_engine:api:qa_sitemap');
    }
}

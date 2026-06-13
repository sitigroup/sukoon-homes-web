<?php

namespace App\Plugins\SeoEngine\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\SeoEngine\Models\SeoEnginePage;
use App\Plugins\SeoEngine\Services\SeoEngineContentService;
use App\Plugins\SeoEngine\Services\SeoEngineSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SeoEngineContentGenerateController extends Controller
{
    private function denyUnlessPages(): void
    {
        if (! function_exists('has_permissions') || ! has_permissions('pages', 'seo_engine')) {
            abort(403);
        }
    }

    public function index(Request $request, SeoEngineContentService $content, SeoEngineSettingsService $settings): View
    {
        $this->denyUnlessPages();

        $q = trim((string) $request->query('q', ''));
        $type = trim((string) $request->query('type', ''));
        $area = trim((string) $request->query('area', ''));
        $missingOnly = $request->boolean('missing_only');

        $minListings = max(0, (int) $settings->get('ai_content_min_listings', 1));

        $pages = SeoEnginePage::query()
            ->where('lock_content', false)
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('path', 'like', '%' . $q . '%')
                    ->orWhere('title', 'like', '%' . $q . '%');
            }))
            ->when($type !== '', fn ($query) => $query->where('page_type', $type))
            ->when($area !== '', fn ($query) => $query->where('path', 'like', '%' . $area . '%'))
            ->when($missingOnly, fn ($query) => $query->where(function ($w) {
                $w->whereNull('intro_html')->orWhere('intro_html', '');
            }))
            ->orderByDesc('listing_count')
            ->orderBy('path')
            ->paginate(50)
            ->withQueryString();

        $types = SeoEnginePage::query()->distinct()->orderBy('page_type')->pluck('page_type');
        $areas = SeoEnginePage::query()
            ->where('path', 'like', '/rent/%')
            ->get(['path'])
            ->map(function ($p) {
                $parts = array_values(array_filter(explode('/', trim($p->path, '/'))));

                return count($parts) >= 3 ? '/rent/' . $parts[1] . '/' . $parts[2] . '/' : null;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('seo-engine::admin.seo-engine.content-generate.index', [
            'pages' => $pages,
            'q' => $q,
            'type' => $type,
            'area' => $area,
            'missingOnly' => $missingOnly,
            'types' => $types,
            'areas' => $areas,
            'minListings' => $minListings,
            'defaultPrompt' => $content->promptTemplateForAdmin(),
            'provider' => (string) $settings->get('ai_provider', 'gemini'),
        ]);
    }

    public function confirm(Request $request, SeoEngineContentService $content): View|RedirectResponse
    {
        $this->denyUnlessPages();

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'prompt_template' => ['nullable', 'string', 'max:20000'],
        ]);

        $pages = SeoEnginePage::query()
            ->whereIn('id', $validated['ids'])
            ->where('lock_content', false)
            ->orderBy('path')
            ->get();

        if ($pages->isEmpty()) {
            return redirect()
                ->route('seo-engine.content-generate.index')
                ->with('error', __('seo-engine::seo_engine.content_generate_none_selected'));
        }

        $token = Str::random(40);
        session([
            'seo_engine_content_generate_token' => $token,
            'seo_engine_content_generate_ids' => $pages->pluck('id')->all(),
        ]);

        $minListings = max(0, (int) app(SeoEngineSettingsService::class)->get('ai_content_min_listings', 1));
        $aiCount = $pages->filter(fn ($p) => (int) $p->listing_count >= $minListings)->count();
        $fallbackCount = $pages->count() - $aiCount;

        return view('seo-engine::admin.seo-engine.content-generate.confirm', [
            'pages' => $pages,
            'token' => $token,
            'promptTemplate' => $content->promptTemplateForAdmin($validated['prompt_template'] ?? null),
            'aiCount' => $aiCount,
            'fallbackCount' => $fallbackCount,
            'minListings' => $minListings,
            'provider' => ucfirst((string) app(SeoEngineSettingsService::class)->get('ai_provider', 'gemini')),
        ]);
    }

    public function run(Request $request, SeoEngineContentService $content): RedirectResponse
    {
        $this->denyUnlessPages();

        $validated = $request->validate([
            'confirm_token' => ['required', 'string'],
            'prompt_template' => ['nullable', 'string', 'max:20000'],
            'confirmed' => ['required', 'accepted'],
        ]);

        $sessionToken = (string) session('seo_engine_content_generate_token', '');
        $sessionIds = session('seo_engine_content_generate_ids', []);

        if ($sessionToken === '' || ! hash_equals($sessionToken, $validated['confirm_token'])) {
            return redirect()
                ->route('seo-engine.content-generate.index')
                ->with('error', __('seo-engine::seo_engine.content_generate_session_expired'));
        }

        if (! is_array($sessionIds) || $sessionIds === []) {
            return redirect()
                ->route('seo-engine.content-generate.index')
                ->with('error', __('seo-engine::seo_engine.content_generate_session_expired'));
        }

        $prompt = trim((string) ($validated['prompt_template'] ?? ''));
        $stats = $content->generateSelected(
            array_map('intval', $sessionIds),
            true,
            $prompt !== '' ? $prompt : null
        );

        session()->forget(['seo_engine_content_generate_token', 'seo_engine_content_generate_ids']);

        return redirect()
            ->route('seo-engine.content.index', ['status' => 'pending'])
            ->with('success', __('seo-engine::seo_engine.content_generate_done', [
                'generated' => $stats['generated'],
                'failed' => $stats['failed'],
                'fallback' => $stats['fallback'],
            ]));
    }
}

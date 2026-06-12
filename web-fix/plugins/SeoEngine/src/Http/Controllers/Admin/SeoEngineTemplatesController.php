<?php

namespace App\Plugins\SeoEngine\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\SeoEngine\Services\SeoEngineTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeoEngineTemplatesController extends Controller
{
    private function denyUnlessTemplates(): void
    {
        if (! function_exists('has_permissions') || ! has_permissions('templates', 'seo_engine')) {
            abort(403);
        }
    }

    public function index(SeoEngineTemplateService $templates): View
    {
        $this->denyUnlessTemplates();
        $templates->seedDefaultsIfEmpty();

        $pageTypes = SeoEngineTemplateService::PAGE_TYPES;
        $active = request('page_type', $pageTypes[0]);
        $current = $templates->latest($active)?->toArray() ?? ($templates->defaults()[$active] ?? []);
        $history = $templates->history($active, 5);
        $sample = [
            'count' => 12,
            'type' => '2 BHK Flat',
            'area' => 'Rai Colony',
            'city' => 'Barmer',
            'subarea' => 'Bariyon Ka Was',
            'min_rent' => 8000,
            'max_rent' => 15000,
            'avg_rent' => 11000,
            'top_landmark' => 'Bus Stand',
        ];
        $preview = $templates->render($active, $sample);

        return view('seo-engine::admin.seo-engine.templates', compact(
            'pageTypes', 'active', 'current', 'history', 'preview', 'sample'
        ));
    }

    public function store(Request $request, SeoEngineTemplateService $templates): RedirectResponse
    {
        $this->denyUnlessTemplates();

        $validated = $request->validate([
            'page_type' => ['required', 'in:' . implode(',', SeoEngineTemplateService::PAGE_TYPES)],
            'title_template' => ['required', 'string', 'max:512'],
            'h1_template' => ['required', 'string', 'max:512'],
            'meta_description_template' => ['nullable', 'string', 'max:2000'],
        ]);

        $templates->save($validated['page_type'], $validated);

        return redirect()
            ->route('seo-engine.templates.index', ['page_type' => $validated['page_type']])
            ->with('success', __('seo-engine::seo_engine.template_saved'));
    }
}

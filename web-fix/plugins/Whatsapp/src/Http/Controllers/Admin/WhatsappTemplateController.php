<?php

namespace App\Plugins\Whatsapp\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\Whatsapp\Models\WaEventMap;
use App\Plugins\Whatsapp\Models\WaTemplate;
use App\Plugins\Whatsapp\Services\MetaGraphClient;
use App\Plugins\Whatsapp\Support\WhatsappEventCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WhatsappTemplateController extends Controller
{
    private function denyUnlessTemplates(): void
    {
        if (! function_exists('has_permissions') || ! has_permissions('templates', 'whatsapp')) {
            abort(403);
        }
    }

    public function index(): View
    {
        $this->denyUnlessTemplates();
        $templates = WaTemplate::query()->orderBy('meta_template_name')->paginate(40);
        $eventOptions = WhatsappEventCatalog::keys();
        $mapByTemplate = WaEventMap::query()->get()->keyBy('template_id');
        return view('whatsapp::admin.whatsapp.templates', compact('templates', 'eventOptions', 'mapByTemplate'));
    }

    public function sync(MetaGraphClient $meta): RedirectResponse
    {
        $this->denyUnlessTemplates();
        $items = $meta->fetchTemplates();
        foreach ($items as $item) {
            $category = (string) ($item['category'] ?? '');
            if (! in_array(strtolower($category), ['utility', 'authentication'], true)) {
                continue;
            }
            $lang = strtolower((string) data_get($item, 'language', 'en'));
            if (! in_array($lang, ['en', 'hi'], true)) {
                continue;
            }

            WaTemplate::query()->updateOrCreate(
                [
                    'meta_template_name' => (string) ($item['name'] ?? ''),
                    'language' => $lang,
                ],
                [
                    'category' => $category,
                    'status' => (string) ($item['status'] ?? ''),
                    'components_json' => $item['components'] ?? [],
                ]
            );
        }
        return back()->with('success', __('whatsapp::whatsapp.templates_synced'));
    }

    public function toggle(WaTemplate $template): RedirectResponse
    {
        $this->denyUnlessTemplates();
        $template->enabled = ! $template->enabled;
        $template->save();
        return back()->with('success', __('whatsapp::whatsapp.saved'));
    }

    public function mapInternalKey(Request $request, WaTemplate $template): RedirectResponse
    {
        $this->denyUnlessTemplates();
        $request->validate([
            'internal_key' => ['required', 'in:' . implode(',', array_merge(['__none__'], WhatsappEventCatalog::keys()))],
            'language' => ['nullable', 'in:en,hi'],
        ]);

        $eventKey = trim($request->string('internal_key')->toString());
        if ($eventKey === '__none__') {
            $eventKey = '';
        }
        $language = $request->string('language')->toString() ?: ($template->language ?: 'en');

        $before = WaEventMap::query()->where('template_id', $template->id)->first();

        WaEventMap::query()->where('template_id', $template->id)->delete();

        if ($eventKey !== '') {
            WaEventMap::query()->where('event_key', $eventKey)->delete();
            $map = WaEventMap::query()->create([
                'event_key' => $eventKey,
                'template_id' => $template->id,
                'enabled' => true,
                'language' => $language,
            ]);
            $template->internal_key = $eventKey;
        } else {
            $map = null;
            $template->internal_key = null;
        }
        $template->save();

        DB::table('wa_audit_log')->insert([
            'actor_id' => auth()->id(),
            'event' => 'template_map_updated',
            'entity_type' => 'template',
            'entity_id' => $template->id,
            'meta_json' => json_encode([
                'template_id' => $template->id,
                'before' => $before ? $before->only(['event_key', 'enabled', 'language']) : null,
                'after' => $map ? $map->only(['event_key', 'enabled', 'language']) : null,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', __('whatsapp::whatsapp.saved'));
    }
}


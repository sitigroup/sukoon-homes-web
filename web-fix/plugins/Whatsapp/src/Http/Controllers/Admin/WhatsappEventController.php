<?php

namespace App\Plugins\Whatsapp\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\Whatsapp\Models\WaEventMap;
use App\Plugins\Whatsapp\Models\WaTemplate;
use App\Plugins\Whatsapp\Support\WhatsappEventCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WhatsappEventController extends Controller
{
    private function denyUnlessEvents(): void
    {
        if (! function_exists('has_permissions')) {
            abort(403);
        }

        if (has_permissions('events', 'whatsapp') || has_permissions('templates', 'whatsapp')) {
            return;
        }

        abort(403);
    }

    public function index(): View
    {
        $this->denyUnlessEvents();

        $events = WhatsappEventCatalog::keys();
        $eventMaps = WaEventMap::query()->with('template')->get()->keyBy('event_key');
        $templates = WaTemplate::query()->orderBy('meta_template_name')->get();

        return view('whatsapp::admin.whatsapp.events', compact('events', 'eventMaps', 'templates'));
    }

    public function save(Request $request): RedirectResponse
    {
        $this->denyUnlessEvents();

        $validated = $request->validate([
            'event_key' => ['required', 'in:' . implode(',', WhatsappEventCatalog::keys())],
            'template_id' => ['nullable', 'integer', 'exists:wa_templates,id'],
            'enabled' => ['nullable', 'boolean'],
            'language' => ['required', 'in:en,hi'],
        ]);

        $eventKey = (string) $validated['event_key'];
        $templateId = $validated['template_id'] ?? null;
        $enabled = (bool) ($validated['enabled'] ?? false);
        $language = (string) $validated['language'];

        if ($enabled && ! $templateId) {
            return back()->with('error', __('whatsapp::whatsapp.event_enabled_without_template'));
        }

        $template = null;
        if ($templateId) {
            $template = WaTemplate::query()->find($templateId);
            if (! $template) {
                return back()->with('error', __('whatsapp::whatsapp.template_not_found'));
            }
        }

        $before = WaEventMap::query()->where('event_key', $eventKey)->first();
        $map = WaEventMap::query()->updateOrCreate(
            ['event_key' => $eventKey],
            [
                'template_id' => $templateId,
                'enabled' => $enabled,
                'language' => $language,
            ]
        );

        DB::table('wa_audit_log')->insert([
            'actor_id' => auth()->id(),
            'event' => 'event_map_updated',
            'entity_type' => 'event',
            'entity_id' => $map->id,
            'meta_json' => json_encode([
                'event_key' => $eventKey,
                'before' => $before ? $before->only(['template_id', 'enabled', 'language']) : null,
                'after' => $map->only(['template_id', 'enabled', 'language']),
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($enabled && $template && strtolower((string) $template->status) !== 'approved') {
            return back()->with('error', __('whatsapp::whatsapp.template_not_approved'));
        }

        return back()->with('success', __('whatsapp::whatsapp.saved'));
    }
}


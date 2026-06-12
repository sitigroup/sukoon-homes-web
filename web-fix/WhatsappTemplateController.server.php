<?php

namespace App\Plugins\Whatsapp\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\Whatsapp\Models\WaEventMap;
use App\Plugins\Whatsapp\Models\WaTemplate;
use App\Plugins\Whatsapp\Services\MetaGraphClient;
use App\Plugins\Whatsapp\Services\WhatsappEventRegistryService;
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
        $eventOptions = app(WhatsappEventRegistryService::class)->allEventsForAdmin();
        $mapByTemplate = WaEventMap::query()->get()->keyBy('template_id');

        return view('whatsapp::admin.whatsapp.templates', compact('templates', 'eventOptions', 'mapByTemplate'));
    }

    public function sync(MetaGraphClient $meta): RedirectResponse
    {
        $this->denyUnlessTemplates();

        $result = $meta->fetchTemplatesResult();
        if (! ($result['ok'] ?? false)) {
            $message = (string) ($result['message'] ?? __('whatsapp::whatsapp.connection_failed'));
            if (isset($result['status'])) {
                $message .= ' (HTTP ' . (int) $result['status'] . ')';
            }
            if (stripos($message, 'access token') !== false || stripos($message, 'session has expired') !== false) {
                $message .= ' ' . __('whatsapp::whatsapp.token_expired_hint');
            }

            return back()->with('error', $message);
        }

        $items = $result['items'] ?? [];
        $synced = 0;
        $skipped = 0;

        foreach ($items as $item) {
            $category = strtolower((string) ($item['category'] ?? ''));
            if (! in_array($category, ['utility', 'authentication'], true)) {
                $skipped++;

                continue;
            }

            $lang = self::normalizeTemplateLanguage((string) data_get($item, 'language', 'en'));
            if (! in_array($lang, ['en', 'hi'], true)) {
                $skipped++;

                continue;
            }

            WaTemplate::query()->updateOrCreate(
                [
                    'meta_template_name' => (string) ($item['name'] ?? ''),
                    'language' => $lang,
                ],
                [
                    'category' => (string) ($item['category'] ?? ''),
                    'status' => (string) ($item['status'] ?? ''),
                    'components_json' => $item['components'] ?? [],
                ]
            );
            $synced++;
        }

        if ($synced === 0 && count($items) > 0) {
            return back()->with(
                'warning',
                __('whatsapp::whatsapp.templates_sync_none_matched', [
                    'total' => count($items),
                    'skipped' => $skipped,
                ])
            );
        }

        if ($synced === 0) {
            return back()->with('warning', __('whatsapp::whatsapp.templates_empty'));
        }

        return back()->with(
            'success',
            __('whatsapp::whatsapp.templates_synced_count', ['count' => $synced, 'total' => count($items)])
        );
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

        $eventKeys = app(WhatsappEventRegistryService::class)->eventKeysForSelect();

        $request->validate([
            'internal_key' => ['required', 'in:' . implode(',', array_merge(['__none__'], $eventKeys))],
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
            $existing = WaEventMap::query()->where('event_key', $eventKey)->first();
            $map = WaEventMap::query()->updateOrCreate(
                ['event_key' => $eventKey],
                [
                    'template_id' => $template->id,
                    'enabled' => true,
                    'language' => $language,
                    'display_name' => $existing?->display_name,
                    'description' => $existing?->description,
                    'event_type' => $existing?->event_type ?? 'custom',
                ]
            );
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

    private static function normalizeTemplateLanguage(string $language): string
    {
        $language = strtolower(str_replace('_', '-', trim($language)));

        if ($language === 'hi' || str_starts_with($language, 'hi-')) {
            return 'hi';
        }

        if ($language === 'en' || str_starts_with($language, 'en-')) {
            return 'en';
        }

        return $language;
    }
}
